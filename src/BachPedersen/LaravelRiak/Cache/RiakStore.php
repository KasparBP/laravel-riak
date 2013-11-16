<?php
/*
   Copyright 2013: Kaspar Bach Pedersen

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

     http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/

namespace BachPedersen\LaravelRiak\Cache;

use Illuminate\Cache\StoreInterface;
use Riak\Bucket;
use Riak\Connection;
use Riak\Input\DeleteInput;
use Riak\Input\GetInput;
use Riak\Input\PutInput;
use Riak\Object;
use Riak\ObjectList;
use Riak\Output\GetOutput;

/**
 * Class RiakStore
 * @package BachPedersen\LaravelRiak\Cache
 *
 * Riak cache storage.
 *
 */
class RiakStore implements StoreInterface
{
    const RIAK_EXPIRES_NAME = 'exp';
    const RIAK_TIMESTAMP_NAME = 'ts';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Bucket
     */
    private $bucket;

    /**
     * @param Connection $connection
     * @param Bucket $bucket
     */
    public function __construct(Connection $connection, Bucket $bucket)
    {
        $this->connection = $connection;
        $this->bucket = $bucket;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string $key
     * @return mixed
     */
    public function get($key)
    {
        $object = $this->getObject($key, $vClock);
        if (!is_null($object)) {
            $content = $object->getContent();
            if (is_string($content) && strlen($content) > 0) {
                return unserialize($content);
            }
        }
        return null;
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param  string $key
     * @param  mixed $value
     * @param  int $minutes
     * @return void
     */
    public function put($key, $value, $minutes)
    {
        $this->performPut($key, $value, $minutes);
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function increment($key, $value = 1)
    {
        $this->mutateNumeric($key, $value);
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function decrement($key, $value = 1)
    {
        $this->mutateNumeric($key, -$value);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function forever($key, $value)
    {
        $this->performPut($key, $value, null);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string $key
     * @return void
     */
    public function forget($key)
    {
        $this->performDelete($key);
    }

    /**
     * Remove all items from the cache.
     *
     * @return void
     */
    public function flush()
    {
        $keys = $this->bucket->getKeyList();
        foreach ($keys as $key) {
            $this->performDelete($key);
        }
    }

    /**
     * Begin executing a new section operation.
     *
     * @param  string  $name
     * @return \Illuminate\Cache\Section
     */
    public function section($name)
    {
        return new RiakSection($this, $name);
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return '';
    }

    /**
     * This function resolves conflicts by looking at timestamp.
     * @param GetOutput $getOutput
     * @param $vClock
     * @return Object|null
     */
    private function resolveAndGetFirst(GetOutput $getOutput, &$vClock) {
        /** @var $result Object */
        $result = null;
        $vClock = $getOutput->getVClock();
        if ($getOutput->hasSiblings()) {
            $newestStamp = 0;
            /** @var $objectList ObjectList */
            $objectList = $getOutput->getObjectList();
            /** @var $obj Object */
            foreach ($objectList as $obj) {
                $metadataMap = $obj->getMetadataMap();
                if (isset($metadataMap[self::RIAK_TIMESTAMP_NAME])) {
                    $ts = intval($metadataMap[self::RIAK_TIMESTAMP_NAME]);
                    if ($ts > $newestStamp) {
                        $newestStamp = $ts;
                        $result = $obj;
                    }
                }
            }
            $putInput = new PutInput();
            $this->bucket->put($result, $putInput->setVClock($vClock));
        } else {
            $result = $getOutput->getFirstObject();
        }
        return $result;
    }

    /**
     * @param \Riak\Object|null $object
     * @param string $vClock
     * @param $now
     * @return Object|null returns the object or null if deleted
     */
    private function deleteIfExpired($object, $vClock, $now) {
        if (is_null($object)) return null;

        $metadataMap = $object->getMetadataMap();
        if (isset($metadataMap[self::RIAK_EXPIRES_NAME])) {
            // Check if key object should be deleted
            $expireTime = intval($metadataMap[self::RIAK_EXPIRES_NAME]);
            if ($expireTime <= $now) {
                // The value expired delete it from riak and return nothing
                $delInput = new DeleteInput();
                $delInput->setVClock($vClock);
                $this->bucket->delete($object, $delInput);
                return null;
            }
        }
        return $object;
    }

    /**
     * Get the contents of a key, this function will make sure the content is delete if too old and resolved if it
     * has conflicts.
     * @param string $key
     * @param $vClock
     * @return null|\Riak\Object
     */
    private function getObject($key, &$vClock)
    {
        $getOutput = $this->bucket->get($key);
        /** @var $obj \Riak\Object */
        $obj = $this->resolveAndGetFirst($getOutput, $vClock);
        $obj = $this->deleteIfExpired($obj, $vClock, time());
        return $obj;
    }

    /**
     * Does a get head and extracts the vclock
     * @param $key
     * @return null|string
     */
    private function getVClock($key)
    {
        $getInput = new GetInput();
        $getInput->setReturnHead(true);
        $getOutput = $this->bucket->get($key, $getInput);
        return $getOutput->getVClock();
    }

    /**
     * @param string $key
     * @param int $value
     */
    private function mutateNumeric($key, $value)
    {
        $object = $this->getObject($key, $vClock);
        $newValue = $value;
        if (!is_null($object)) {
            $content = $object->getContent();
            if (is_string($content) && strlen($content) > 0) {
                $newValue = intval(unserialize($content)) + $value;
            }
        } else {
            $object = new Object($key);
        }
        $object->setContent(serialize($newValue));
        $putInput = new PutInput();
        $putInput->setVClock($vClock);
        $this->bucket->put($object, $putInput);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $minutes
     */
    private function performPut($key, $value, $minutes)
    {
        // Start by doing a get head so we are sure to use latest vclock
        $vClock = $this->getVClock($key);

        $obj = new Object($key);
        $putTimeStamp = time();
        if (!is_null($minutes)) {
            // Save a timestamp for when when this value expires.
            $obj->addMetadata(self::RIAK_EXPIRES_NAME, $putTimeStamp + ($minutes * 60));
        }
        // Save the put time as well, if for some reason mults have been enabled on the bucket, we use this for resolving.
        $obj->addMetadata(self::RIAK_TIMESTAMP_NAME, $putTimeStamp);
        $obj->setContent(serialize($value));

        $putInput = new PutInput();
        $putInput->setVClock($vClock);
        $this->bucket->put($obj, $putInput);
    }

    /**
     * @param $key
     */
    private function performDelete($key)
    {
        $vClock = $this->getVClock($key);
        $delInput = new DeleteInput();
        $delInput->setVClock($vClock);
        $this->bucket->delete($key, $delInput);
    }
}