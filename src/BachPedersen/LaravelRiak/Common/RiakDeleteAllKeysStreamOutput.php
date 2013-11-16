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
use Riak\Output\KeyStreamOutput;

class RiakDeleteAllKeysStreamOutput implements KeyStreamOutput
{
    /**
     * @var \Riak\Bucket
     */
    private $bucket;

    public function __construct(Bucket $bucket)
    {
        $this->bucket = $bucket;
    }

    /**
     * @param string $key received a key from riak
     * @return void
     */
    public function process($key)
    {
        Operations::deleteWithVClock($this->bucket, $key);
    }

}