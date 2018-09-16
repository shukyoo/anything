<?php namespace Job;

use Predis\Client;

require_once __DIR__ .'/LuaScript.php';

/**
 * 默认存到redis队列
 * 如果redis保存失败，则存到数据库
 */
class JobQueue
{
    protected $queue_name = 'mall_job';

    public function __construct($queue_name)
    {
        $this->queue_name = $queue_name;
    }

    /**
     * 事件入队
     */
    public function push($event_name, array $params = [], $job_name = '', $job_method = '', $delay = 0)
    {
        $job = Job::newJob($event_name, $params, $job_name, $job_method, $delay);

        // 先保存到redis
        try {
            $res = $this->pushToRedis($job);
        } catch (\Exception $e) {
            $res = false;
            \Logger::error('job_push_redis', $e->getMessage(), ['job' => $job->toArray(), 'trace' => $e->getTrace()]);
        }

        // 如果redis保存失败，则保存到数据库
        if (!$res) {
            try {
                $res = $this->pushToDb($job);
            } catch (\Exception $e) {
                $res = false;
                \Logger::error('queue_to_db', '任务无法推送到redis且无法推送到DB', array(
                    'event_name' => $event_name,
                    'params' => $params,
                    'job_name' => $job_name,
                    'job_method' => $job_method,
                    'delay' => $delay
                ));
            }
        }

        return (bool)$res;
    }

    /**
     * 获取队列中事件总数
     */
    public function count()
    {
        $queue = $this->getQueueName();

        return $this->getRedis()->eval(
            LuaScript::size(), 3, $queue, $queue.':delayed', $queue.':reserved'
        );
    }

    /**
     * 保存到redis队列
     * 如果成功返回队列中的数量，失败返回false
     * 如果是延时任务，则保存到延时任务集合中
     */
    public function pushToRedis(Job $job)
    {
        if ($job->delay > 0) {
            return $this->later($job->delay, $job);
        } else {
            return $this->rpush($job);
        }
    }

    /**
     * 保存到DB队列
     */
    public function pushToDb(Job $job)
    {
        $sql = 'INSERT INTO job_queue(job_id, event_name, params, job_name, job_method, delay, create_time) VALUES(?,?,?,?,?,?,?)';
        return DbQuery::execute($sql, array(
            $job->job_id, $job->event_name,
            json_encode($job->params, JSON_UNESCAPED_UNICODE),
            $job->job_name, $job->job_method, $job->delay, $job->create_time
        ));
    }


    /**
     * 出队
     * 检查延时任务和超时任务，重新入队
     * 先从redis出队，出队事件加入保留zset中，等执行成功删除
     * （如果zset中有超时的则重新需要加回队列中）
     */
    public function pop($retry_after = 60)
    {
        $this->migrate();

        $queue = $this->getQueueName();
        $available_at = time() + $retry_after;
        list($job, $reserved) = $this->getRedis()->eval(
            LuaScript::pop(), 2, $queue, $queue.':reserved', $available_at
        );
        if ($job && $reserved) {
            return Job::loadJob($job, $reserved);
        }
        return null;
    }

    /**
     * 删除保留的事件
     */
    public function deleteReserved(Job $job)
    {
        $this->getRedis()->zrem($this->getQueueName('reserved'), $job->getReserved());
    }

    /**
     * 获取reserve超时任务
     * 默认超过600秒,10分钟
     */
    public function getFailedReserves($limit = 600)
    {
        return $this->getRedis()->zrangebyscore($this->getQueueName('reserved'), 0, time() - $limit);
    }


    /**
     * 加入队列
     */
    protected function rpush(Job $job)
    {
        return $this->getRedis()->rpush($this->getQueueName(), $job->toJson());
    }

    /**
     * 延迟
     */
    protected function later($delay, Job $job)
    {
        $delay_to = time() + $delay;
        return $this->getRedis()->zadd($this->getQueueName('delayed'), $delay_to, $job->toJson());
    }

    /**
     * 把delay和reserve重新入队
     */
    protected function migrate()
    {
        // 把delayed重新迁移到queue
        $this->getRedis()->eval(
            LuaScript::migrateExpiredJobs(), 2, $this->getQueueName('delayed'), $this->getQueueName(), time()
        );
        // 把reserved重新迁移到queue
        $this->getRedis()->eval(
            LuaScript::migrateExpiredJobs(), 2, $this->getQueueName('reserved'), $this->getQueueName(), time()
        );
    }


    /**
     * 如果任务失败，则加入一个延时集合中，再重新入队，基于最大重试配置
     */
    public function deleteAndRelease(Job $job, $retry_after = 60)
    {
        $queue = $this->getQueueName();

        $available_at = time() + $retry_after;
        $this->getRedis()->eval(
            LuaScript::release(), 2, $queue.':delayed', $queue.':reserved', $job->getReserved(), $available_at
        );
    }


    /**
     * 获取队列名称
     */
    protected function getQueueName($sub = '')
    {
        $name = $this->queue_name;

        // 以下用于兼容redis hash tag，保证queue在同一台机器
        if (RedisConn::isCluster()) {
            $name = '{'. $name .'}';
        }
        if ($sub) {
            $name .= (':'. $sub);
        }
        return $name;
    }


    /**
     * @return null|Client
     */
    protected function getRedis()
    {
        return RedisConn::getConnection();
    }
}