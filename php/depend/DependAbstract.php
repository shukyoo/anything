<?php namespace Depend;


abstract class DependAbstract
{
    protected $id;
    protected $params = [];
    protected $depend_msg = '';

    public function __construct(array $params, $id = 0)
    {
        $this->params = $params;

        foreach ($params as $k => $v) {
            if ($k == 'id') {
                throw new \Exception('id is reserved');
            }
            if (property_exists($this, $k)) {
                $this->$k = $v;
            } else {
                $method = 'set'. str_replace('_', '', ucwords($k, '_'));
                if (method_exists($this, $method)) {
                    $this->$method($v);
                }
            }
        }

        $this->id = $id;
        $this->init();
    }

    protected function init()
    {
    }

    public function pass()
    {
        if ($this->isValid()) {
            // 检验通过，尝试运行
            // 如果运行失败，则加入等待队列中，后续再运行
            return $this->fire();
        } elseif ($this->id) {
            $sql = 'UPDATE sys_task_depend SET reserved=0, detects=detects+1, depend_msg="'. $this->depend_msg .'", update_time="'. getDatetime() .'" WHERE id='. $this->id;
            \DbQuery::execute($sql);
            return true;
        } else {
            return $this->add();
        }
    }


    protected function fire()
    {
        try {

            return \DbQuery::transaction(function () {

                $res = $this->handle();
                if ($this->id) {
                    \DbQuery::execute('DELETE FROM sys_task_depend WHERE id='. $this->id);
                }

                return $res;

            });

        } catch (\Exception $e) {
            if ($this->id) {
                \DbQuery::execute('UPDATE sys_task_depend SET reserved=0, detects=detects+1, attempts=attempts+1, errmsg=?, update_time=? WHERE id='. $this->id, [$e->getMessage(), getDatetime()]);
            }
            throw $e;
        }
    }

    protected function add()
    {
        $info = array(
            'task_name' => get_called_class(),
            'params' => $this->params,
            'depend_msg' => $this->depend_msg,
            'detects' => 1
        );
        $task_depend = new \SysTaskDepend();
        $task_depend->fill($info);
        $res = $task_depend->save();
        if (!$res) {
            $msg = get_called_class() .' create fail';
            \Logger::error('task_depend', $msg, $info);
            throw new \Exception($msg);
        }
        return $res;
    }


    abstract protected function isValid();

    abstract protected function handle();
}