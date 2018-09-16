<?php
require dirname(__DIR__) . '/init.php';
require ROOT_PATH . '/vendor/autoload.php';
Logger::setLogApi(\Job\Config::get('log_url'));//兼容脚本使用Logger

defined('JOB_DEBUG') || define('JOB_DEBUG', 0);
defined('JOB_TRACE') || define('JOB_TRACE', 0);