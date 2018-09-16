<?php namespace Job;

class DbQuery
{
    protected static $pdo;


    public static function ensureConnection()
    {
        if (is_null(self::$pdo)) {
            self::connect();
        }
        try {
            $res = @self::fetchOne('select 1');
            if (!$res) {
                self::reconnect();
            }
        } catch (\Exception $e) {
            if (self::causedByLostConnection($e)) {
                self::reconnect();
            } else {
                throw $e;
            }
        }
    }

    /**
     * @return \PDO
     */
    public static function getPdo()
    {
        if (null === self::$pdo) {
            self::connect();
        }
        return self::$pdo;
    }

    public static function reconnect()
    {
        self::$pdo = null;
        self::connect();
    }

    protected static function connect()
    {
        try {
            self::$pdo = self::createConnection();
        } catch (\Exception $e) {
            if (self::causedByLostConnection($e)) {
                self::$pdo = self::createConnection();
            }
            throw $e;
        }
    }

    /**
     * @return \PDO
     */
    protected static function createConnection()
    {
        $config = Config::get('db');
        $options = isset($config['options']) ? $config['options'] : [];
        $options = array_diff_key([\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION], $options) + $options;
        return new \PDO($config['dsn'], $config['username'], $config['password'], $options);
    }


    public static function beginTransaction()
    {
        try {
            self::getPdo()->beginTransaction();
        } catch (\Exception $e) {
            if (self::causedByLostConnection($e)) {
                self::reconnect();
                self::getPdo()->beginTransaction();
            } else {
                throw $e;
            }
        }
    }

    public static function commit()
    {
        self::getPdo()->commit();
    }

    public static function rollBack()
    {
        self::getPdo()->rollBack();
    }


    /**
     * fetch all array with assoc, empty array returned if nothing or false
     * @param string $sql
     * @param mixed $bind
     * @return array
     */
    public static function fetchAll($sql, $bind = null)
    {
        return self::selectPrepare($sql, $bind)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * fetch all with firest field as indexed key, empty array returned if nothing or false
     * @param string $sql
     * @param mixed $bind
     * @return array
     */
    public static function fetchAllIndexed($sql, $bind = null)
    {
        return self::selectPrepare($sql, $bind)->fetchAll(\PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC);
    }

    /**
     * fetch all grouped array with first field as keys, empty array returned if nothing or false
     * @param string $sql
     * @param mixed $bind
     * @return array
     */
    public static function fetchAllGrouped($sql, $bind = null)
    {
        return self::selectPrepare($sql, $bind)->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_ASSOC);
    }

    /**
     * fetch one row array with assoc, empty array returned if nothing or false
     * @param string $sql
     * @param mixed $bind
     * @return array
     */
    public static function fetchRow($sql, $bind = null)
    {
        return self::selectPrepare($sql, $bind)->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * fetch first column array, empty array returned if nothing or false
     * @param string $sql
     * @param mixed $bind
     * @return array
     */
    public static function fetchColumn($sql, $bind = null)
    {
        return self::selectPrepare($sql, $bind)->fetchAll(\PDO::FETCH_COLUMN, 0);
    }

    /**
     * fetch pairs of first column as Key and second column as Value, empty array returned if nothing or false
     * @param string $sql
     * @param mixed $bind
     * @return array
     */
    public static function fetchPairs($sql, $bind = null)
    {
        return self::selectPrepare($sql, $bind)->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    /**
     * fetch grouped pairs of K/V with first field as keys of grouped array, empty array returned if nothing of false
     * @param string $sql
     * @param mixed $bind
     * @return array
     */
    public static function fetchPairsGrouped($sql, $bind = null)
    {
        $data = [];
        foreach (self::selectPrepare($sql, $bind)->fetchAll(\PDO::FETCH_NUM) as $row) {
            $data[$row[0]] = [$row[1] => $row[2]];
        }
        return $data;
    }

    /**
     * fetch one column value, false returned if nothing or false
     * @param string $sql
     * @param mixed $bind
     * @return mixed
     */
    public static function fetchOne($sql, $bind = null)
    {
        return self::selectPrepare($sql, $bind)->fetchColumn(0);
    }


    /**
     * @param $sql
     * @return \PDOStatement
     */
    public static function selectPrepare($sql, $bind = null, $fetch_mode = null)
    {
        $stmt = self::getPdo()->prepare($sql);
        $stmt->execute(self::bind($bind));
        if (null !== $fetch_mode) {
            $stmt->setFetchMode($fetch_mode);
        }
        return $stmt;
    }

    /**
     * Execute an SQL statement and return the boolean result.
     * @param string $sql
     * @param mixed $bind
     * @param int &$affected_rows
     * @return bool
     */
    public static function execute($sql, $bind = null, &$affected_rows = 0)
    {
        $stmt = self::getPdo()->prepare($sql);
        $res = $stmt->execute(self::bind($bind));
        $affected_rows = $stmt->rowCount();
        return $res;
    }

    /**
     * Get last insert id
     * @return int|string
     */
    public static function getLastInsertId()
    {
        return self::getPdo()->lastInsertId();
    }

    /**
     * Parse bind as array
     * @param mixed $bind
     * @return null|array
     */
    protected static function bind($bind)
    {
        if ($bind === null) {
            return null;
        }
        if (!is_array($bind)) {
            $bind = [$bind];
        }
        return array_values($bind);
    }


    protected static function causedByLostConnection(\Exception $e)
    {
        $message = $e->getMessage();
        return self::contains($message, [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
            'Transaction() on null',
            'child connection forced to terminate due to client_idle_limit',
        ]);
    }

    protected static function contains($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        return false;
    }
}