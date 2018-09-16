<?php

class Cache
{
    const DEFAULT_EXPIRATION = 86400;

    /**
     * Get cache data
     * @param $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        $value = Application::getRedis()->get($key);
        return is_null($value) ? $default : self::unserialize($value);
    }

    /**
     * Set cache data
     * @param string $key
     * @param mixed $value
     * @param int $expiration second,0 for forever
     */
    public static function set($key, $value, $expiration = null)
    {
        if (null === $expiration) {
            $expiration = self::DEFAULT_EXPIRATION;
        }
        Application::getRedis()->setex(
            $key, $expiration, self::serialize($value)
        );
    }

    /**
     * Delete cache data
     * @param string $key
     * @return bool
     */
    public static function delete($key)
    {
        return Application::getRedis()->del($key);
    }

    /**
     * Remove all items from the cache.
     * @return void
     */
    public static function flush()
    {
        Application::getRedis()->flushdb();
    }

    /**
     * Increment the value of an item in the cache.
     * @param  string  $key
     * @param  int   $offset
     * @return int|false return the new value or false
     */
    public static function increment($key, $offset = 1)
    {
        return Application::getRedis()->incrby($key, $offset);
    }

    /**
     * Decrement the value of an item in the cache.
     * @param  string  $key
     * @param  int   $offset
     * @return int|false return the new value or false
     */
    public static function decrement($key, $offset = 1)
    {
        return Application::getRedis()->decrby($key, $offset);
    }

    /**
     * Get the data
     * If date not exists, the data from the callback will be set to the cache
     *
     * @param string $key
     * @param mixed $data_source
     * @param int $expiration
     * @param bool $is_reset
     * @return mixed
     */
    public static function getData($key, $data_source = null, $expiration = null, $is_reset = false)
    {
        $key = strtolower($key);
        $data = self::get($key);
        if (null === $data || $is_reset) {
            $data = self::setData($key, $data_source, $expiration);
        }
        return $data;
    }

    /**
     * Set the data to the cache
     *
     * @param string $key
     * @param mixed $data_source
     * @param int $expiration
     * @return mixed
     */
    public static function setData($key, $data_source, $expiration = null)
    {
        is_callable($data_source) && $data_source = $data_source();
        if (!empty($data_source)) {
            $key = strtolower($key);
            self::set($key, $data_source, $expiration);
        }
        return $data_source;
    }


    /**
     * Serialize the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected static function serialize($value)
    {
        return is_numeric($value) ? $value : serialize($value);
    }

    /**
     * Unserialize the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected static function unserialize($value)
    {
        return is_numeric($value) ? $value : unserialize($value);
    }
}