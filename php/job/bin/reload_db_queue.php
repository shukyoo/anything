<?php
/**
 * 数据库中失败的queue重新入队
 */
require __DIR__ .'/init.php';

use Job\DbQuery;

if (DbQuery::fetchOne('SELECT COUNT(*) FROM job_queue FOR UPDATE')) {
    $jobs = DbQuery::fetchAll('SELECT * FROM job_queue LIMIT 100 FOR UPDATE');
    foreach ($jobs as $job) {

        $job_queue = new \Job\JobQueue(\Job\Config::get('queue')['queue_name']);
        $job_entity = \Job\Job::loadJob($job);
        $res = $job_queue->pushToRedis($job_entity);

        if ($res) {
            DbQuery::execute('DELETE FROM job_queue WHERE id="'. $job['id'] .'"');
        }
    }
}