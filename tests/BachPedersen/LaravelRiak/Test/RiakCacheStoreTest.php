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

/**
 * Class RiakCacheStoreTest
 * This test will most likely only work on Riak 1.4 and above
 */
class RiakCacheStoreTest extends \PHPUnit_Framework_TestCase
{
    const TEST_BUCKET_NAME = 'riak.cache.unittest.nomults';
    /**
     * @var \BachPedersen\LaravelRiak\Cache\RiakStore
     */
    private $store;

    public function setUp()
    {
        $conn = new \Riak\Connection('localhost');
        $bucket = $conn->getBucket(self::TEST_BUCKET_NAME);
        $bucketProperties = new \Riak\BucketPropertyList();
        $bucketProperties->setAllowMult(false)
                         ->setLastWriteWins(false);
        $bucket->setPropertyList($bucketProperties);
        $this->store = new \BachPedersen\LaravelRiak\Cache\RiakStore($conn, $bucket);
        $this->store->flush();
    }

    public function testSimpleGetNotFound()
    {
        $notHere = $this->store->get("dummy");
        $this->assertNull($notHere, "We should not get a result with a key that does not exist");
    }

    public function testSimplePutGet()
    {
        $value = 'ÅÆØ Ole bole gik i skole, så kom johny "drop table forbi med nogle sjove karakterer #€%€%%&//&(&!';
        $this->store->put('testVal', $value, 10);
        $gotten = $this->store->get('testVal');
        $this->assertEquals($value, $gotten);
    }

    public function testIncrementNonExisting()
    {
        $value = 10;
        $this->store->increment('testInc', $value);
        $gotten = $this->store->get('testInc');
        $this->assertEquals($value, $gotten);
    }

    public function testIncrementDecrement()
    {
        $value = 10;
        $this->store->put('testIncDec', 0, 10);
        $this->store->increment('testIncDec', $value);
        $gotten = $this->store->get('testIncDec');
        $this->assertEquals($value, $gotten);
        $this->store->decrement('testIncDec', 5);
        $gotten = $this->store->get('testIncDec');
        $this->assertEquals($value-5, $gotten);
    }

    public function testDeletesTooOld()
    {
        $value = "dummy";
        // Save for 0 minutes = should be deleted on first get
        $this->store->put('testDelVal', $value, 0);
        $gotten = $this->store->get('testDelVal');
        $this->assertNull($gotten);
    }

    public function testForget()
    {
        $value = "forgetMe";
        // Save for 0 minutes = should be deleted on first get
        $this->store->put('forgetMe', $value, 10);
        $gotten = $this->store->get('forgetMe');
        $this->assertEquals($value, $gotten);
        $this->store->forget('forgetMe');
        $gotten = $this->store->get('forgetMe');
        $this->assertNull($gotten);
    }

    public function testDeleteLotsOfCacheData()
    {
        echo "put start: ".time().PHP_EOL;
        for ($i=0;$i<1000; ++$i) {
            $this->store->put("key$i", $i, 100);
        }
        echo "flush start: ".time().PHP_EOL;
        $this->store->flush();
        echo "finished at: ".time().PHP_EOL;
    }
} 