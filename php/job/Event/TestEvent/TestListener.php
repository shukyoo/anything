<?php namespace Job\Event\TestEvent;

use Job\Event\TestEvent;

class TestListener
{
    public function handle(TestEvent $event)
    {
        //throw new \Exception('test exception');
        echo 'wow';
        echo $event->getId();
    }

    public function sayHello(TestEvent $event)
    {
        echo 'hello';
        echo $event->getId();
    }
}