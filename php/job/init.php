<?php
define('JOB_PATH', __DIR__);
define('ROOT_PATH', dirname(JOB_PATH));
define('CONF_PATH', ROOT_PATH .'/config/local');
require_once __DIR__ .'/Config.php';
require_once __DIR__ .'/RedisConn.php';
require_once __DIR__ .'/helper.php';

if (class_exists('Yii') && !empty(Yii::$app->components['db'])) {

    \Job\Config::set(array(
        'db' => array(
            'dsn' => Yii::$app->components['db']['dsn'],
            'username' => Yii::$app->components['db']['username'],
            'password' => Yii::$app->components['db']['password'],
            'options' => [\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']
        ),

        'log_url' => Yii::$app->params['log_url'],

        // 用户服务RPC接口
        'user_rpc_domain' => Yii::$app->params['user_rpc_domain'],

        // 订单服务接口
        'order_rpc_domain' => Yii::$app->params['order_rpc_domain'],

        // 快递服务接口
        'express_domain' => Yii::$app->params['express']['domain'],

        // 队列名称
        'queue' => Yii::$app->params['job_queue'],
    ));

    if (!empty(Yii::$app->params['redis_cluster'])) {
        \Job\RedisConn::setConfig(Yii::$app->params['redis_cluster']);
    } else {
        \Job\RedisConn::setConfig(array(
            'host' => Yii::$app->params['redis']['servers']['default']['host'],
            'port' => Yii::$app->params['redis']['servers']['default']['port'],
            'database' => Yii::$app->params['redis']['servers']['default']['db'],
            'persistent' => true,
            'timeout' => 10
        ));
    }


} else {

    $config = include CONF_PATH .'/base.php';
    $db = $config['components']['db'];

    \Job\Config::set(array(
        'db' => array(
            'dsn' => $db['dsn'],
            'username' => $db['username'],
            'password' => $db['password'],
            'options' => [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']
        ),

        'log_url' => $config['params']['log_url'],

        // 用户服务RPC接口
        'user_rpc_domain' => $config['params']['user_rpc_domain'],

        // 订单服务接口
        'order_rpc_domain' => $config['params']['order_rpc_domain'],

        // 快递服务接口
        'express_domain' => $config['params']['express']['domain'],

        // 队列名称
        'queue' => $config['params']['job_queue']
    ));

    if (!empty($config['params']['redis_cluster'])) {
        \Job\RedisConn::setConfig($config['params']['redis_cluster']);
    } else {
        $redis = $config['params']['redis']['servers']['default'];
        \Job\RedisConn::setConfig(array(
            'host' => $redis['host'],
            'port' => $redis['port'],
            'database' => $redis['db'],
            'persistent' => true,
            'timeout' => 10
        ));
    }

}