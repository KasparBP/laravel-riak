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

namespace BachPedersen\LaravelRiak\Session;

use Riak\Bucket;
use Riak\Connection;
use Riak\Object;

class RiakSessionHandler implements \SessionHandlerInterface {

    const SESSION_TIMESTAMP_NAME = 'ts_bin';

    /**
     * @var \Riak\Connection
     */
    private $connection;

    /**
     * @var Bucket
     */
    private $bucket;

    public function __construct(Connection $connection, $bucketName)
    {
        $this->connection = $connection;
        $this->bucket = $connection->getBucket($bucketName);
    }

    /**
     * @inheritdoc
     */
    public function close()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function destroy($session_id)
    {
        $this->bucket->delete($session_id);
    }

    /**
     * @inheritdoc
     */
    public function gc($maxlifetime)
    {
        $tsMax = time() - $maxlifetime;
        $keys = $this->bucket->index(static::SESSION_TIMESTAMP_NAME, 0, $tsMax);
        if (is_array($keys)) {
            foreach ($keys as $key) {
                $this->bucket->delete($key);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function open($save_path, $session_id)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function read($session_id)
    {
        $getOutput = $this->bucket->get($session_id);
        $obj = $getOutput->getFirstObject();
        if (isset($obj) && !$obj->isDeleted()) {
            return $obj->getContent();
        }
        return "";
    }

    /**
     * @inheritdoc
     */
    public function write($session_id, $session_data)
    {
        $obj = new Object($session_id);
        $obj->setContentType('text/plain');
        $obj->setContent($session_data);
        $obj->addIndex(static::SESSION_TIMESTAMP_NAME, time());
        $this->bucket->put($obj);
    }

}