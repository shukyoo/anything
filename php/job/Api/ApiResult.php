<?php namespace Job\Api;


class ApiResult
{
    protected $res = false;
    protected $msg;
    protected $code;
    protected $data;

    /**
     * [
     *     'res' => 1,
     *     'msg' => 'xxxx',
     *     'code' => 0,
     *     'hello' => 'world'
     * ]
     */
    public function __construct($result = [])
    {
        $this->set($result);
    }

    public function set($result)
    {
        if (isset($result['res']) && $result['res'] == 1) {
            $this->res = true;
        } else {
            $this->msg = empty($result['msg']) ? 'response error' : $result['msg'];
            $this->code = empty($result['code']) ? -1 : $result['code'];
            $call = debug_backtrace();
            \Logger::error($call[2]['class'] .'.'. $call[2]['function'], $this->msg, array(
                'param' => $call[2]['args'],
                'res' => $result
            ));
        }
        unset($result['res'], $result['msg'], $result['code']);
        $this->data = $result;
        return $this;
    }

    public function success()
    {
        return $this->res;
    }

    public function fail()
    {
        return !$this->res;
    }

    public function getMessage()
    {
        return $this->msg;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getData()
    {
        return $this->data;
    }

    public function __get($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }
}