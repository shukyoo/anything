<?php

class DbQuery
{
    /**
     * @return \PDO
     */
    public static function getPdo()
    {
        return DB::getPdo();
    }

    public static function ensureConnection()
    {
        try {
            $res = @self::fetchOne('select 1');
            if (!$res) {
                DB::reconnect();
            }
        } catch (\Exception $e) {
            if (self::causedByLostConnection($e)) {
                DB::reconnect();
            } else {
                throw $e;
            }
        }
    }

    /**
     * 自定义事务
     * 用于处理事务内部异步事件的问题
     */
    public static function transaction(Closure $callback)
    {
        DB::beginTransaction();
        try {
            return tap($callback(), function ($result) {
                DB::commit();
                if (DB::transactionLevel() == 0) {
                    Event::queuePush();
                }
            });
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }


    /**
     * fetch all array with assoc, empty array returned if nothing or false
     * @param string $sql
     * @param mixed $bind
     * @return array
     */
    public static function fetchAll($sql, $bind = null)
    {
        return self::selectPrepare($sql, $bind)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * fetch all with firest field as indexed key, empty array returned if nothing or false
     * @param string $sql
     * @param mixed $bind
     * @return array
     */
    public static function fetchAllIndexed($sql, $bind = null)
    {
        return self::selectPrepare($sql, $bind)->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    }

    /**
     * fetch all grouped array with first field as keys, empty array returned if nothing or false
     * @param string $sql
     * @param mixed $bind
     * @return array
     */
    public static function fetchAllGrouped($sql, $bind = null)
    {
        return self::selectPrepare($sql, $bind)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
    }

    /**
     * fetch one row array with assoc, empty array returned if nothing or false
     * @param string $sql
     * @param mixed $bind
     * @return array
     */
    public static function fetchRow($sql, $bind = null)
    {
        return self::selectPrepare($sql, $bind)->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * fetch first column array, empty array returned if nothing or false
     * @param string $sql
     * @param mixed $bind
     * @return array
     */
    public static function fetchColumn($sql, $bind = null)
    {
        return self::selectPrepare($sql, $bind)->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * fetch pairs of first column as Key and second column as Value, empty array returned if nothing or false
     * @param string $sql
     * @param mixed $bind
     * @return array
     */
    public static function fetchPairs($sql, $bind = null)
    {
        return self::selectPrepare($sql, $bind)->fetchAll(PDO::FETCH_KEY_PAIR);
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
        foreach (self::selectPrepare($sql, $bind)->fetchAll(PDO::FETCH_NUM) as $row) {
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
     * @return PDOStatement
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
        return $bind;
    }

    protected static function causedByLostConnection(Exception $e)
    {
        $message = $e->getMessage();

        return \Illuminate\Support\Str::contains($message, [
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
}