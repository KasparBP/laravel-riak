[![Build Status](https://travis-ci.org/TriKaspar/laravel-riak.png?branch=master)](https://travis-ci.org/TriKaspar/laravel-riak)
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

##Cache provider
A Riak caching provider is not done yet, check back later.  
  
##Session provider
A Riak session provider is not done yet, check back later.  
  
##Usage  
To get a Riak\Connection, simply ask the app for the instance.  
```PHP
/** @var $riak \Riak\Connection */
$riak = $this->app['riak'];

// or
/** @var $riak \Riak\Connection */
$riak = App::make('riak');

```  

##Links  
composer homepage: http://getcomposer.org/
php_riak pecl page: http://pecl.php.net/package/riak  
php_riak source: https://github.com/TriKaspar/php_riak
php_riak documentation: http://phpriak.bachpedersen.dk/
