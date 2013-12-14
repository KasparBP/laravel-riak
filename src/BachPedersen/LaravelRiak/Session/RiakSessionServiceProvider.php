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

use BachPedersen\LaravelRiak\Console\BucketInitCommand;
use Illuminate\Support\ServiceProvider;
use Riak\BucketPropertyList;
use Riak\Connection;

class RiakSessionServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app['session']->extend('riak', function($app)
        {
            /** @var $riak Connection */
            $riak = $app['riak'];
            $bucket = $app['config']['session.bucket'];
            return new RiakSessionHandler($riak, $bucket);
        });
        $this->registerCommands();
    }

    /**
     * Register the session related console commands.
     *
     * @return void
     */
    public function registerCommands()
    {
        $this->app['command.session.bucket'] = $this->app->share(function($app)
        {
            $properties = new BucketPropertyList();
            $properties
                ->setAllowMult(false)
                ->setLastWriteWins(false);
            return new BucketInitCommand('session:bucket:init', $app['riak'], $app['config']['session.bucket'], $properties);
        });
        $this->commands('command.session.bucket');
    }
}