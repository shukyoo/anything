<?php
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;

class Application extends Container
{
    /**
     * @var self
     */
    protected static $instance;


    public static function boot()
    {
        self::$instance = self::getInstance();

        // boot db
        self::getDb()->setAsGlobal();
        self::getDb()->bootEloquent();

        // boot queue
        self::getQueue()->setAsGlobal();

        // class_alias
        class_alias('Illuminate\Database\Capsule\Manager', 'DB');
        class_alias('Illuminate\Queue\Capsule\Manager', 'Queue');
    }

    /**
     * @return \Illuminate\Database\Capsule\Manager
     */
    public static function getDb()
    {
        return self::$instance->get('db');
    }

    /**
     * @return \Illuminate\Redis\Connections\PredisClusterConnection
     */
    public static function getRedis()
    {
        return self::$instance->get('redis');
    }

    /**
     * @return Illuminate\Queue\Capsule\Manager
     */
    public static function getQueue()
    {
        return self::$instance->get('queue');
    }

    /**
     * @return Dispatcher
     */
    public static function getEventDispatcher()
    {
        return Container::getInstance()->get(Dispatcher::class);
    }


    public function __construct()
    {
        $this->regDb();
        $this->regRedis();
        $this->regQueue();
    }


    /**
     * Determine if the application is in maintenance mode.
     * @return bool
     */
    public function isDownForMaintenance() {
        return false;
    }

    /**
     * 绑定Redis组件
     */
    protected function regRedis()
    {
        // redis绑定
        $this->singleton('redis', function () {
            $conf = Config::get('redis');
            if (!empty($conf['host'])) {
                // 单redis
                $conf = array(
                    'default' => $conf
                );
            } else {
                // cluster
                $conf = array(
                    'clusters' => $conf
                );
            }
            //return new \Illuminate\Redis\RedisManager('phpredis', $conf);
            return new \Illuminate\Redis\RedisManager('predis', $conf);
        });
    }

    /**
     * 绑定DB组件
     */
    protected function regDb()
    {
        $this->singleton('db', function () {
            $capsule = new \Illuminate\Database\Capsule\Manager($this);
            $capsule->addConnection(Config::get('db'));
            return $capsule;
        });
    }

    /**
     * 绑定队列组件
     */
    protected function regQueue()
    {
        $this->bind('queue', function () {
            $queue = new Illuminate\Queue\Capsule\Manager($this);
            $queue->addConnection(Config::get('queue'), 'default');
            return $queue;
        });

        Container::getInstance()->singleton(Dispatcher::class, function () {
            return (new \Illuminate\Events\Dispatcher($this))->setQueueResolver(function () {
                return $this->get('queue');
            });
        });
    }
}