<?php namespace Job;
use Job\Event\EventAbstract;
use yii\base\ErrorException;

/**
 * 任务处理
 */
class JobHandler
{
    protected $retry_times = 3;
    protected $retry_after = 10;  // 重试延时
    protected $job_timeout = 60;  // 单任务超时，到时会被重新入队
    protected $job_queue;
    protected $listeners = [];

    public function __construct(JobQueue $job_queue, $listeners = [], $conf = [])
    {
        $this->job_queue = $job_queue;
        $this->listeners = $listeners;

        // 配置
        if (!empty($conf['retry_times'])) {
            $this->retry_times = $conf['retry_times'];
        }
        if (!empty($conf['retry_after'])) {
            $this->retry_after = $conf['retry_after'];
        }
        if (!empty($conf['job_timeout'])) {
            $this->job_timeout = $conf['job_timeout'];
        }
    }

    /**
     * 获取下一个job
     * @return Job|null
     * @throws \ErrorException
     * @throws \Exception
     */
    public function getNextJob()
    {
        try {
            return $this->job_queue->pop($this->job_timeout);
        } catch (\Exception $e) {
            \Logger::error('get_next_job', $e->getMessage(), $e->getTrace());
            throw $e;
        } catch (\Throwable $e) {
            \Logger::error('get_next_job', $e->getMessage(), $e->getTrace());
            throw new \ErrorException(
                $e->getMessage(),
                $e->getCode(),
                E_ERROR,
                $e->getFile(),
                $e->getLine()
            );
        }
    }

    /**
     * 每次出队一条处理
     * 问题：
     * 如果失败：
     * 1. 是否存在远程已调用成功，本地失败的情况，这时候重试会导致远程重复执行
     * 2. 如果本地成功，远程失败，那么本地是否会存在重复执行的情况
     * 因此暂时只把失败任务记录下来，不做自动重试，之后再想办法优化
     */
    public function handle(Job $job)
    {
        // 确保连接
        DbQuery::ensureConnection();

        $exception = null;
        try {

            $event = $this->getEvent($job->event_name, $job->params);
            if ($job->job_name) {

                // 单任务
                $this->fire($event, $job->job_name, $job->job_method);

            } else {

                // 事件，监听者
                foreach ($this->getEventListeners($job->event_name) as $listener) {
                    $this->fire($event, $listener);
                }

            }

            // 完成，删除reserve状态
            $this->job_queue->deleteReserved($job);

        } catch (\Exception $e) {
            $exception = $e;
        } catch (\Throwable $e) {
            $exception = new ErrorException(
                $e->getMessage(),
                $e->getCode(),
                E_ERROR,
                $e->getFile(),
                $e->getLine()
            );
        }

        if ($exception) {
            $this->handleFailed($job, $exception);
            throw $exception;
        }
    }

    /**
     * 单任务执行
     */
    protected function fire(EventAbstract $event, $job_name, $job_method = '')
    {
        $method = $job_method ?: 'handle';
        $job = new $job_name();
        $job->$method($event);

        \Logger::info('job_handle', $job_name .' -> '. $job_method, $event->getParams());
    }


    /**
     * 处理失败的任务
     */
    protected function handleFailed(Job $job, \Exception $e)
    {
        // 任务在重试范围内，则重新加入延后重试集合中
        // 在重试范围内的不记失败记录
        if ($job->attempts < $this->retry_times) {
            $this->job_queue->deleteAndRelease($job, $this->retry_after);
        } else {
            $this->logFailed($job, $e->getMessage());
            $this->job_queue->deleteReserved($job);
        }
    }

    /**
     * 失败记录
     */
    protected function logFailed(Job $job, $errmsg = '')
    {
        $datetime = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO job_failed(job_id, event_name, params, job_name, job_method, errmsg, attempts, job_create_time, create_time, update_time) VALUES(?,?,?,?,?,?,?,?,?,?)';
        DbQuery::execute($sql, array(
            $job->job_id, $job->event_name,
            json_encode($job->params, JSON_UNESCAPED_UNICODE),
            $job->job_name, $job->job_method, $errmsg, $job->attempts, $job->create_time, $datetime, $datetime
        ));
        \Logger::error('job_handle_failed', $errmsg, $job->toArray());
    }


    protected function getEvent($event_name, $params)
    {
        return new $event_name($params);
    }

    protected function getEventListeners($event_name)
    {
        return isset($this->listeners[$event_name]) ? $this->listeners[$event_name] : [];
    }

}