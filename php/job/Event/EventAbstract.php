<?php namespace Job\Event;

use Job\Config;
use Job\JobQueue;

class EventAbstract
{
    protected $params = [];

    public function __construct(array $params = [])
    {
        $this->params = $params;

        foreach ($params as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            } else {
                $method = 'set' . str_replace('_', '', ucwords($k, '_'));
                if (method_exists($this, $method)) {
                    $this->$method($v);
                }
            }
        }
    }

    public function getName()
    {
        return get_class($this);
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getValue($name)
    {
        return isset($this->params[$name]) ? $this->params[$name] : null;
    }

    public function __get($name)
    {
        return $this->getValue($name);
    }

    public static function push($params, $delay = 0)
    {
        return self::getQueue()->push(get_called_class(), $params, '', '', $delay);
    }

    public static function getQueue()
    {
        static $job_queue = null;
        if (null === $job_queue) {
            require_once dirname(__DIR__) .'/init.php';
            $job_queue = new JobQueue(Config::get('queue')['queue_name']);
        }
        return $job_queue;
    }
}