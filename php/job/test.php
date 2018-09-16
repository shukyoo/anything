<?php
require __DIR__ .'/init.php';
require ROOT_PATH .'/vendor/autoload.php';


// {"job_method":"","job_name":"","job_id":"151237345497493075","event_name":"Job\\Event\\OrderCreateEvent","attempts":3,"create_time":"2017-12-04 15:44:14","delay":0,"params":{"order_id":"2017120462790647576","frozen_types":{"BL3ORA":1}}}

/*
$params = json_decode('{"order_id":"2017120462790647576","frozen_types":{"BL3ORA":1}}', true);
$event = new \Job\Event\OrderCreateEvent($params);
$listener = new \Job\Event\OrderCreate\OrderServiceSyncListener();
$listener->handle($event);
*/

\Job\Cache::set('test', ['a' => 1, 'b' => 2]);
$a = \Job\Cache::get('test');
print_r($a);