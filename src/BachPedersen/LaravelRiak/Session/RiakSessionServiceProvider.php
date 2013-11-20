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

use Illuminate\Session\CacheBasedSessionHandler;
use Illuminate\Session\SessionServiceProvider;
use Session;

class RiakSessionServiceProvider extends SessionServiceProvider
{

    public function boot()
    {
        $this->package('bach-pedersen/laravel-riak');
    }

    public function register()
    {
        parent::register();
        Session::extend('riak', function($app)
        {
            $lifetime = $this->app['config']['session.lifetime'];
            return new CacheBasedSessionHandler($this->app['cache']->driver('riak'), $lifetime);
        });
    }
} 