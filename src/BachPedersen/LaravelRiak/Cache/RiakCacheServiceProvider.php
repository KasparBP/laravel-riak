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

use BachPedersen\LaravelRiak\Console\BucketInitCommand;
use Illuminate\Cache\Repository;
use Illuminate\Support\Manager;
use Illuminate\Support\ServiceProvider;
use Riak\BucketPropertyList;
use Riak\Connection;
use Cache;

class RiakCacheServiceProvider extends ServiceProvider
{
    const DEFAULT_BUCKET_NAME = 'laravel.cache';

    public function boot()
    {
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->resolving('cache', function($cache) {
            /** @var Manager $cache */
            $cache->extend('riak', function ($app) {
                /** @var $riak Connection */
                $riak = $app['riak'];
                $bucketName = $app['config']['cache.bucket'];
                if (!isset($bucketName)) {
                    $bucketName = self::DEFAULT_BUCKET_NAME;
                }
                return new Repository(new RiakStore($riak, $riak->getBucket($bucketName)));
            });
        });
        $this->registerCommands();
    }

    /**
     * Register the cache related console commands.
     *
     * @return void
     */
    public function registerCommands()
    {
        $this->app['command.cache.bucket'] = $this->app->share(function($app)
        {
            $properties = new BucketPropertyList();
            $properties
                ->setAllowMult(false)
                ->setLastWriteWins(false);
            return new BucketInitCommand('cache:bucket:init', $app['riak'], $app['config']['cache.bucket'], $properties);
        });
        $this->commands('command.cache.bucket');
    }

}