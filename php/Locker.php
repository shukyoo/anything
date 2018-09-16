<?php

class Locker
{
    const PREFIX = 'olk_';
    const WAIT = 25;  // 锁等待
    const TIMEOUT = 15;  // 锁超时

    /**
     * 是否锁定中
     */
    public static function isLock($key)
    {
        return self::getRedis()->get(self::getKey($key)) > time();
    }

    /**
     * 锁
     * 并发进入等待中，最多等待{WAIT}秒
     * 如果加锁失败则返回false
     */
    public static function lock($key, $timeout = null)
    {
        if (!$timeout) {
            $timeout = self::TIMEOUT;
        }
        $wait = $timeout + 5;
        $key = self::getKey($key);
        $redis = self::getRedis();
        $time = time();
        while (time() - $time < $wait) {
            if ($redis->set($key, time() + $timeout, 'EX', $timeout, 'NX')) {
                // 加锁成功。
                return true;
            }
            // 未能加锁成功。
            // 检查当前锁是否已过期，并重新锁定。
            if ($redis->get($key) < time()) {
                $redis->del($key);
                $redis->set($key, time() + $timeout, 'EX', $timeout, 'NX');
                return true;
            }
            usleep(100000);
        }
        return false;
    }


    /**
     * 直接排它，不等待
     */
    public static function getLock($key, $timeout = 30)
    {
        $redis = self::getRedis();
        $key = self::getKey($key);
        return (bool)$redis->set($key, time() + $timeout, 'EX', $timeout, 'NX');
    }

    /**
     * 解锁
     */
    public static function unlock($key)
    {
        self::getRedis()->del(self::getKey($key));
    }

    protected static function getKey($key)
    {
        return self::PREFIX . $key;
    }

    /**
     * @return \Illuminate\Redis\Connections\PhpRedisConnection
     */
    protected static function getRedis()
    {
        return Application::getRedis();
    }
}