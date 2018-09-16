<?php namespace Job\Event;

class TestEvent extends EventAbstract
{
    public function getId()
    {
        if (!$this->id) {
            throw new \Exception('invalid params');
        }
        return $this->id;
    }
}
