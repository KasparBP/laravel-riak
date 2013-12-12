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

use Illuminate\Cache\Section;
use Illuminate\Cache\StoreInterface;
use Riak\Bucket;
use Riak\Connection;
use Riak\Input\DeleteInput;
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
    const RIAK_EXPIRES_NAME = 'exp_int';
    const RIAK_TIMESTAMP_NAME = 'ts_int';

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
        $vClock = null;
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
        $this->bucket->delete($key);
    }

    /**
     * Remove all items from the cache.
     *
     * @return void
     */
    public function flush()
    {
        $to = time() + 1;
        $keys = $this->bucket->index(self::RIAK_TIMESTAMP_NAME, "0", "$to");
        if (isset($keys)) {
            foreach ($keys as $key) {
                $this->forget($key);
            }
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
        return new Section($this, $name);
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
     * @param \Riak\Object|null $object
     * @param $now
     * @return Object|null returns the object or null if deleted
     */
    private function deleteIfExpired($object, $now) {
        if (is_null($object)) return null;
        if ($object->isDeleted()) return null;

        $indexMap = $object->getIndexMap();
        if (isset($indexMap[self::RIAK_EXPIRES_NAME])) {
            // Check if key object should be deleted
            $expireTime = intval($indexMap[self::RIAK_EXPIRES_NAME]);
            if ($expireTime <= $now) {
                // The value expired delete it from riak and return nothing
                $this->bucket->delete($object);
                return null;
            }
        }
        return $object;
    }

    /**
     * Get the contents of a key, this function will make sure the content is delete if too old and resolved if it
     * has conflicts.
     * @param string $key
     * @return null|\Riak\Object
     */
    private function getObject($key)
    {
        $getOutput = $this->bucket->get($key);
        /** @var $obj \Riak\Object */
        $obj = $getOutput->getFirstObject();
        $obj = $this->deleteIfExpired($obj, time());
        return $obj;
    }

    /**
     * @param string $key
     * @param int $value
     */
    private function mutateNumeric($key, $value)
    {
        $vClock = null;
        $object = $this->getObject($key, $vClock);
        $newValue = $value;
        if (!is_null($object)) {
            $content = $object->getContent();
            if (is_string($content) && strlen($content) > 0) {
                $newValue = intval(unserialize($content)) + $value;
            }
        }
        $this->performPut($key, $newValue);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $minutes
     */
    private function performPut($key, $value, $minutes = null)
    {
        $obj = new Object($key);
        $putTimeStamp = time();
        if (!is_null($minutes)) {
            // Save a timestamp for when when this value expires.
            $obj->addIndex(self::RIAK_EXPIRES_NAME, $putTimeStamp + ($minutes * 60));
        }
        $obj->addIndex(self::RIAK_TIMESTAMP_NAME, $putTimeStamp);
        $obj->setContent(serialize($value));
        $this->bucket->put($obj);
    }

}