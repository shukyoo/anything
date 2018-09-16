<?php namespace Job;

use Predis\Client;

class RedisConn
{
    protected static $config;
    protected static $redis;
    protected static $cluster;
    protected static $is_cluster = false;

    public static function setConfig($config)
    {
        self::$config = $config;
        self::$is_cluster = !empty($config['seeds']);
    }

    /**
     * @return Client
     */
    public static function getConnection()
    {
        /*
        if (self::$is_cluster) {
            return self::getCluster();
        } else {
            return self::getRedis();
        }
        */
        if (self::$is_cluster) {
            return self::getPredisCluster();
        } else {
            return self::getPredisClient();
        }
    }

    public static function isCluster()
    {
        return self::$is_cluster;
    }

    /**
     * @return \Redis
     */
    protected static function getRedis()
    {
        if (null === self::$redis) {
            self::$redis = new \Redis();
            $timeout = isset(self::$config['timeout']) ? self::$config['timeout'] : 0;
            if (isset(self::$config['persistent']) && self::$config['persistent'] == true) {
                self::$redis->pconnect(self::$config['host'], self::$config['port'], $timeout);
            } else {
                self::$redis->connect(self::$config['host'], self::$config['port'], $timeout);
            }
            if (!empty(self::$config['password'])) {
                self::$redis->auth(self::$config['password']);
            }
            if (!empty(self::$config['options'])) {
                foreach (self::$config['options'] as $k=>$v) {
                    self::$redis->setOption($k, $v);
                }
            }
            if (isset(self::$config['database'])) {
                self::$redis->select(self::$config['database']);
            }
        }
        return self::$redis;
    }

    /**
     * @return \RedisCluster
     */
    protected static function getCluster()
    {
        if (null === self::$cluster) {
            if (!empty(self::$config['name'])) {
                self::$cluster = new \RedisCluster(self::$config['name']);
            } else {
                $options = isset(self::$config['options']) ? self::$config['options'] : [];
                $timeout = isset($options['timeout']) ? $options['timeout'] : 0;
                $read_timeout = isset($options['read_timeout']) ? $options['read_timeout'] : 0;
                $persistent = isset($options['persistent']) ? $options['persistent'] : false;
                $seeds = array_map(function(array $server){
                    return $server['host'] .':'. $server['port'];
                }, self::$config['seeds']);
                self::$cluster = new \RedisCluster(NULL, $seeds, $timeout, $read_timeout, $persistent);
            }
        }
        return self::$cluster;
    }

    /**
     * @return Client
     */
    protected static function getPredisClient()
    {
        if (null === self::$redis) {
            $options = null;
            if (isset(self::$config['options'])) {
                $options = self::$config['options'];
                unset(self::$config['options']);
            }
            self::$redis = new Client(self::$config, $options);
        }
        return self::$redis;
    }

    /**
     * @return Client
     */
    protected static function getPredisCluster()
    {
        if (null === self::$cluster) {
            $options = isset(self::$config['options']) ? self::$config['options'] : null;
            self::$cluster = new Client(self::$config['seeds'], $options);
        }
        return self::$cluster;
    }
}