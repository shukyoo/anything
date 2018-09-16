<?php
/**
 * 执行失败的任务
 * 第一个参数可以指定失败的具体ID
 * 如果没有指定则会全部重试
 * 最多在原来基础上重试3次
 */
require __DIR__ .'/init.php';


// 获取配置的重试次数
$retry_times = isset(\Job\Config::get('queue')['retry_times']) ? \Job\Config::get('queue')['retry_times'] : 3;
$max_times = $retry_times + 3;

$data = [];
if (!empty($argv[1])) {
    $id = (int)$argv[1];
    // 获取指定的失败任务
    $sql = 'SELECT * FROM job_failed WHERE id='. $id;
    $row = \Job\DbQuery::fetchRow($sql);
    if (empty($row)) {
        echo 'data not exists';
        exit;
    }
    $data[] = $row;
} else {
    $sql = 'SELECT * FROM job_failed WHERE attempts<'. $max_times .' ORDER BY id ASC';
    $data = \Job\DbQuery::fetchAll($sql);
}

$pdo = \Job\DbQuery::getPdo();

foreach ($data as $item) {


    $pdo->beginTransaction();

    try {

        $params = json_decode($item['params'], true);
        $event = new $item['event_name']($params);

        if (empty($item['job_name'])) {

            foreach (get_event_listeners($item['event_name']) as $listener) {
                job_handle($event, $listener);
            }

        } else {

            job_handle($event, $item['job_name'], $item['job_method']);

        }

        \Job\DbQuery::execute('DELETE FROM job_failed WHERE id='. $item['id']);

        $pdo->commit();

    } catch (\Exception $e) {

        $pdo->rollBack();

        \Job\DbQuery::execute('UPDATE job_failed SET errmsg=?,attempts=attempts+1 WHERE id='. $item['id'], $e->getMessage());

        Logger::error('job_retry', $e->getMessage(), $e->getTrace());

        if (JOB_DEBUG) {
            echo $e->getMessage() ."\n";
        }
    }

}


function job_handle(\Job\Event\EventAbstract $event, $job_name, $job_method = '')
{
    $method = $job_method ?: 'handle';
    $job = new $job_name();
    $job->$method($event);
}


function get_event_listeners($event_name)
{
    static $regs = null;
    if (null === $regs) {
        $regs = include ROOT_PATH .'/job/Event/register.php';
    }
    return isset($regs[$event_name]) ? $regs[$event_name] : [];
}