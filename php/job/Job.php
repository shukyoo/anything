<?php namespace Job;

class Job
{
    /**
     * @param $event_name
     * @param array $params
     * @param string $job_name
     * @param string $job_method
     * @param int $delay
     * @return Job
     * @throws \Exception
     */
    public static function newJob($event_name, array $params = [], $job_name = '', $job_method = '', $delay = 0)
    {
        if (!$event_name) {
            throw new \Exception('event_name required for new job');
        }
        $job = new self();
        $job->job_id = self::genId();
        $job->event_name = $event_name;
        $job->params = $params;
        $job->job_name = $job_name;
        $job->job_method = $job_method;
        $job->delay = (int)$delay;
        $job->attempts = 0;
        $job->create_time = date('Y-m-d H:i:s');
        return $job;
    }

    /**
     * @param $info
     * @return Job
     */
    public static function loadJob($info, $reserved = '')
    {
        if (is_string($info)) {
            $info = json_decode($info, true);
        }
        if (empty($info['params'])) {
            $info['params'] = [];
        } elseif (!is_array($info['params'])) {
            $info['params'] = json_decode($info['params'], true);
        }
        $job = new Job();
        $job->setInfo($info);
        if ($reserved) {
            $job->reserved = $reserved;
        }
        return $job;
    }

    /**
     * 生成ID
     * @return string
     */
    protected static function genId()
    {
        $tm = floor(microtime(true) * 10000);
        $rand = str_pad(mt_rand(10, 9999), 4, '0', STR_PAD_LEFT);
        return $tm . $rand;
    }

    // -----------------------------------------------

    public $job_id = '';
    public $event_name = '';
    public $params = [];
    public $job_name = '';
    public $job_method = '';
    public $delay = 0;
    public $attempts = 0;
    public $create_time = '';
    public $reserved = '';

    public function setInfo(array $info)
    {
        foreach ($info as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
    }

    public function toArray()
    {
        return array(
            'job_id' => $this->job_id,
            'event_name' => $this->event_name,
            'params' => $this->params,
            'job_name' => $this->job_name,
            'job_method' => $this->job_method,
            'delay' => $this->delay,
            'attempts' => $this->attempts,
            'create_time' => $this->create_time
        );
    }

    public function toJson()
    {
        return json_encode($this->toArray());
    }

    public function getReserved()
    {
        return $this->reserved;
    }
}