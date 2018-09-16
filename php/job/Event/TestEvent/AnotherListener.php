<?php namespace Job\Event\TestEvent;

use Job\Event\TestEvent;
use Job\Job;
use Job\JobQueue;

class AnotherListener
{
    public function handle(TestEvent $event)
    {
        // 延后10秒执行
        TestEvent::getQueue()->push($event->getName(), $event->getParams(), static::class, 'delayed', 10);
    }

    public function delayed()
    {
        echo 'Im delayed';
    }
}