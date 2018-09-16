<?php namespace Job;


class JobWorker
{
    protected $sleep = 3;
    protected $memory = 128;
    protected $timeout = 0;  // 0为不超时，如果设置了可能会引起由于任务不断而中断，因为不是每一条超时，所以暂不使用
    protected $is_debug = false;
    protected $is_trace = false;
    protected $trace_id = 0;

    public $quit = false;
    public $pause = false;

    protected $job_handler;

    public function __construct(JobHandler $handler, $conf = [])
    {
        $this->job_handler = $handler;
        if (!empty($conf['sleep'])) {
            $this->sleep = $conf['sleep'];
        }
        if (!empty($conf['memory'])) {
            $this->memory = $conf['memory'];
        }
        if (!empty($conf['timeout'])) {
            $this->timeout = $conf['timeout'];
        }
        if (!empty($conf['is_debug'])) {
            $this->is_debug = $conf['is_debug'];
        }
        if (!empty($conf['is_trace'])) {
            $this->is_trace = $conf['is_trace'];
        }
    }

    /**
     * 运行
     */
    public function run()
    {
        // SIG信号监听注册
        $this->listenSignals();
        $this->trace('job_start');

        while (true) {

            if (extension_loaded('pcntl')) {
                pcntl_signal_dispatch();
            }

            // 暂停处理
            if ($this->pause) {
                $this->pauseWorker();
                continue;
            }

            // 获取一条任务
            $job = $this->getNextJob();

            // 注册执行超时
            // $this->regTimeout();

            // 运行任务
            // 一直清空完才sleep
            if ($job) {
                $this->runJob($job);
            } else {
                sleep($this->sleep);
            }

            // 检测是否需要结束进程
            $this->checkStop();
        }
    }

    /**
     * 获取下一条任务
     * @return Job|null
     */
    protected function getNextJob()
    {
        try {
            return $this->job_handler->getNextJob();
        } catch (\Exception $e) {
            $this->trace('get_next_job_exception', $e->getMessage());
            $this->debugHandle($e);
            $this->stopIfLostConnection($e);
        }
    }

    /**
     * 执行任务
     * @param Job $job
     */
    protected function runJob(Job $job)
    {
        try {
            $this->trace('run_job_start', ['job_id' => $job->job_id, 'event' => $job->event_name]);

            // 执行任务
            $this->job_handler->handle($job);

            $this->trace('run_job_finish', ['job_id' => $job->job_id, 'event' => $job->event_name]);

        } catch (\Exception $e) {
            $this->trace('run_job_error', ['msg' => $e->getMessage(), 'job_id' => $job->job_id, 'event' => $job->event_name]);
            // 如果debug模式，则直接显示出异常信息，否则不作处理，因为在job_handler内部已经作了失败处理
            $this->debugHandle($e);
            $this->stopIfLostConnection($e);

        }
    }

    /**
     * debug模式下信息显示处理
     * @param \Exception $e
     */
    protected function debugHandle(\Exception $e)
    {
        if ($this->is_debug) {
            echo "\n";
            echo 'msg: ' . $e->getMessage() . "\n";
            echo 'file:' . $e->getFile() . '[' . $e->getLine() . ']' . "\n";
            echo '----------------------------------------------' ."\n";
        }
    }

    /**
     * 注册监听信号处理
     */
    protected function listenSignals()
    {
        if (extension_loaded('pcntl')) {
            pcntl_signal(SIGINT, function () {
                $this->trace('signal_SIGINT');
                $this->quit = true;
            });
            pcntl_signal(SIGTERM, function () {
                $this->trace('signal_SIGTERM');
                $this->quit = true;
            });
            pcntl_signal(SIGUSR2, function () {
                $this->trace('signal_SIGUSR2');
                $this->pause = true;
            });
            pcntl_signal(SIGCONT, function () {
                $this->trace('signal_SIGCONT');
                $this->pause = false;
            });
        }
    }

    /**
     * 注册超时
     */
    protected function regTimeout()
    {
        if (extension_loaded('pcntl')) {
            pcntl_signal(SIGALRM, function () {
                $this->kill(1);
            });

            pcntl_alarm($this->timeout);
        }
    }


    /**
     * 暂停进程
     */
    public function pauseWorker()
    {
        $this->trace('job_pause');
        sleep($this->sleep);
        $this->checkStop();
    }

    /**
     * 检测是否需要结束
     */
    public function checkStop()
    {
        if ($this->quit) {
            $this->kill();
        }
        if ((memory_get_usage() / 1024 / 1024) >= $this->memory) {  // 内存超出
            \Logger::error('job_worker', 'memory exhausted and worker will be restart');
            exit(12);
        }
    }

    /**
     * 结束进程
     */
    public function kill($status = 0)
    {
        $this->trace('job_killed');
        if (extension_loaded('posix')) {
            posix_kill(getmypid(), SIGKILL);
        }
        exit($status);
    }

    /**
     * 是否出现失去连接的异常，如果失去连接则退出进程
     */
    protected function stopIfLostConnection(\Exception $e)
    {
        $is_lost = $this->contains($e->getMessage(), [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'Connection lost',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
            'Transaction() on null',
            'child connection forced to terminate due to client_idle_limit',
        ]);
        if ($is_lost) {
            $this->trace('lost_connection', $e->getMessage());
            $this->quit = true;
        }
    }

    protected function contains($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    protected function trace($step, $params = null)
    {
        if ($this->is_trace) {
            if (!$this->trace_id) {
                $this->trace_id = time();
            }
            \Logger::info('job_trace', $this->trace_id .'--'. $step, $params);
        }
    }
}