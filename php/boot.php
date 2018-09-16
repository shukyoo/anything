<?php
define('ROOT_PATH', __DIR__);

require ROOT_PATH .'/vendor/autoload.php';
require ROOT_PATH .'/config/app.php';


// Init Eloquent
use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->addConnection(Config::get('db'));

// Set the event dispatcher used by Eloquent models... (optional)
// use Illuminate\Events\Dispatcher;
// use Illuminate\Container\Container;
// $capsule->setEventDispatcher(new Dispatcher(new Container));

// Make this Capsule instance available globally via static methods... (optional)
$capsule->setAsGlobal();

// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
$capsule->bootEloquent();


// alias
class_alias('Illuminate\Database\Capsule\Manager', 'DB');

