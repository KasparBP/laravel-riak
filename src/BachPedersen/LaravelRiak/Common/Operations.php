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

namespace BachPedersen\LaravelRiak\Common;

use Riak\Bucket;
use Riak\Input\DeleteInput;
use Riak\Input\GetInput;

/**
 * Class Operations
 * @package BachPedersen\LaravelRiak\Common
 * Performs common riak operations.
 */
class Operations
{
    /**
     * Empties a bucket completely
     * @param Bucket $bucket
     */
    public static function emptyBucket(Bucket $bucket)
    {
        $deleteStream = new RiakDeleteAllKeysStreamOutput($bucket);
        $bucket->getKeyStream($deleteStream);
    }

    /** Performs a delete, gets the vclock first and then includes the vclock in the delete call
     * @param Bucket $bucket
     * @param $key
     */
    public static function deleteWithVClock(Bucket $bucket, $key)
    {
        $vClock = self::getVClock($bucket, $key);
        $delInput = new DeleteInput();
        $delInput->setVClock($vClock);
        $bucket->delete($key, $delInput);
    }

    /**
     * Does a get head and extracts the vclock
     * @param \Riak\Bucket $bucket
     * @param $key
     * @return null|string
     */
    public static function getVClock(Bucket $bucket, $key)
    {
        $getInput = new GetInput();
        $getInput->setReturnHead(true);
        $getOutput = $bucket->get($key, $getInput);
        return $getOutput->getVClock();
    }
} 