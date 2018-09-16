package main

import (
	"fmt"
	"os"
	"time"

	"github.com/gomodule/redigo/redis"
)

func newPool() *redis.Pool {
	host := os.Getenv("REDIS_HOST")

	return &redis.Pool{
		MaxIdle:     10,
		IdleTimeout: 240 * time.Second,
		Dial: func() (redis.Conn, error) {
			c, err := redis.Dial("tcp", host)
			if err != nil {
				return nil, err
			}
			return c, err
		},
		TestOnBorrow: func(c redis.Conn, t time.Time) error {
			if time.Since(t) < time.Minute {
				return nil
			}
			_, err := c.Do("PING")
			return err
		},
	}
}

//var pool = newPool()

type store struct {
	pool   *redis.Pool
	prefix string
}

func newStore() *store {
	return &store{pool: newPool(), prefix: "_"}
}

func (s *store) get(key string) (interface{}, error) {
	conn := s.pool.Get()
	defer conn.Close()
	return conn.Do("GET", s.getKey(key))
}

func (s *store) set(key string, val interface{}, timeout time.Duration) error {
	conn := s.pool.Get()
	defer conn.Close()
	_, err := conn.Do("SETEX", s.getKey(key), int64(timeout/time.Second), val)
	return err
}

func (s *store) delete(key string) error {
	conn := s.pool.Get()
	defer conn.Close()
	_, err := conn.Do("DEL", s.getKey(key))
	return err
}

func (s *store) getKey(key string) string {
	return fmt.Sprintf("%s%s", s.prefix, key)
}
