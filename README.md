[![Build Status](https://travis-ci.org/TriKaspar/laravel-riak.png?branch=master)](https://travis-ci.org/TriKaspar/laravel-riak)[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/TriKaspar/laravel-riak/badges/quality-score.png?s=b914de3041d326452b9a55d99231654b7ce1325b)](https://scrutinizer-ci.com/g/TriKaspar/laravel-riak/)
#laravel-riak  
Simple Riak provider for Laravel.  

##Requirements  
This provider is built on top of php_riak so it of cause requires then php_riak extension to be installed.  
Installation instructions can be found here http://phpriak.bachpedersen.dk/installation/  

##Installation  
Add bach-pedersen/laravel-riak to your composer.json like this:  
```JSON
"require": {
    "bach-pedersen/laravel-riak": "dev-master"
}
```  
##Configuration  
Host and port name for Riak should be configured in your app/config/database.php like this:
```PHP
/*
    |--------------------------------------------------------------------------
    | Riak Database
    |--------------------------------------------------------------------------
    */
    'riak' => array(
        'host' => 'localhost',
        'port' => 8087
    )
```  
Remember php-riak uses riak protobuf interface and not the http interface, default port is 8087.  
  
Also the service provider should be registered in your app/config/app.php file, like this:
```PHP
/*
// File: app/config/app.php
'providers' => array(
        ...
        'BachPedersen\LaravelRiak\RiakServiceProvider',
),
```  

##Usage  
To get a Riak\Connection, simply ask the app for the instance.  
```PHP
/** @var $riak \Riak\Connection */
$riak = $this->app['riak'];

// or
/** @var $riak \Riak\Connection */
$riak = App::make('riak');

```  
  

##Cache provider
There is also a caching provider included that can be activated if desired.  
To activate the caching provider, make sure the normal Riak provider is configured like above, and then do the following:  
1: Add provider in app
```PHP
// File: app/config/app.php
'providers' => array(
        ...
        'BachPedersen\LaravelRiak\Cache\RiakCacheServiceProvider',
        ...
),
```  
2: Change the default cache driver and set the name of the bucket where you want the cache to be stored, like this:  
```PHP
// File: app/config/cache.php
    ...
	'driver' => 'riak',
	'bucket' => 'laravel.cache',
    ...
```  
  
##Session provider
The session provider is built on top of the cache provider so both that and the regular riak provider should be added in app.php  
Beside that the session provider should be added like this:  
```PHP
// File: app/config/app.php
'providers' => array(
        ...
        'BachPedersen\LaravelRiak\Session\RiakSessionServiceProvider',
        ...
),
```  
And the same way as the cache provider the following settings should set in session.php  
```PHP
// File: app/config/session.php
    ...
	'driver' => 'riak',
	'bucket' => 'laravel.session',
    ...
```  
  
##Links  
composer homepage: http://getcomposer.org/  
php_riak pecl page: http://pecl.php.net/package/riak  
php_riak source: https://github.com/TriKaspar/php_riak  
php_riak documentation: http://phpriak.bachpedersen.dk/  
