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

namespace BachPedersen\LaravelRiak\Console;

use Illuminate\Console\Command;
use Riak\BucketPropertyList;
use Riak\Connection;

class BucketInitCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'auth:bucket';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $bucketName;
    /**
     * @var \Riak\BucketPropertyList
     */
    private $bucketProperties;

    /**
     * @param string $commandName name of this command
     * @param Connection $connection riak connection
     * @param string $bucketName bucket name to set
     * @param BucketPropertyList $bucketProperties
     */
    public function __construct($commandName, Connection $connection,
                                $bucketName, BucketPropertyList $bucketProperties)
    {
        $this->name = $commandName;
        $this->connection = $connection;
        $this->bucketName = $bucketName;
        $this->bucketProperties = $bucketProperties;
        parent::__construct();
    }

    /**
     * Execute the console command.
     * @return void
     */
    public function fire()
    {
        $this->connection->getBucket($this->bucketName)->setPropertyList($this->bucketProperties);
        $this->info('Bucket properties set!');
    }
} 