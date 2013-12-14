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

use BachPedersen\LaravelRiak\Session\RiakSessionHandler;

class RiakSessionHandlerTest extends \PHPUnit_Framework_TestCase {
    const TEST_BUCKET_NAME = 'riak.cache.unittest.nomults';

    /**
     * @var RiakSessionHandler
     */
    private $handler;

    public function setUp()
    {
        $conn = new \Riak\Connection('localhost');
        $bucket = $conn->getBucket(self::TEST_BUCKET_NAME);
        $bucketProperties = new \Riak\BucketPropertyList();
        $bucketProperties->setAllowMult(false)
            ->setLastWriteWins(false);
        $bucket->setPropertyList($bucketProperties);
        $this->handler = new RiakSessionHandler($conn, self::TEST_BUCKET_NAME);
    }

    public function testReadWrite() {
        $sessionId = 'testsession';
        $this->handler->open(null, $sessionId);
        $this->handler->write($sessionId, 'test_data');
        $readData = $this->handler->read($sessionId);
        $this->handler->close();
        $this->handler->destroy($sessionId);

        $this->assertEquals('test_data', $readData);
    }

    public function testDestroy()
    {
        $sessionId = 'testdestroy';
        $this->handler->write($sessionId, 'test_data');
        $this->handler->destroy($sessionId);
        $readData = $this->handler->read($sessionId);
        $this->assertTrue($readData == "");
    }

    public function testGc()
    {
        $sessionId = 'testgc';
        $this->handler->write($sessionId, 'test_data');
        $this->handler->gc(0);
        $readData = $this->handler->read($sessionId);
        $this->handler->destroy($sessionId);
        $this->assertTrue($readData == "");
    }

} 