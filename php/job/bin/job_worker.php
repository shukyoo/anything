<?php
/**
 * 事件异步处理进程
 */
require __DIR__ .'/init.php';

// 配置
$job_conf = \Job\Config::get('queue');

$job_queue = new \Job\JobQueue($job_conf['queue_name']);
$job_handler = new \Job\JobHandler(
    $job_queue,
    include JOB_PATH .'/Event/register.php',
    $job_conf
);

$job_worker = new \Job\JobWorker($job_handler, ['is_debug' => JOB_DEBUG, 'is_trace' => JOB_TRACE]);
$job_worker->run();
