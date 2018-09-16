<?php


class Result
{
    protected $status = false;
    protected $msg;
    protected $data;

    public function __construct($result = null)
    {
        if ($result) {
            $this->set($result);
        }
    }

    public function set($result)
    {
        if (isset($result['status']) && $result['status'] == true) {
            $this->status = true;
        } else {
            $this->status = false;
            $this->msg = isset($result['msg']) ? $result['msg'] : 'failed';
        }
        if (isset($result['data'])) {
            $this->data = $result['data'];
        }
        return $this;
    }

    public function success($data = null)
    {
        $this->status = true;
        $this->data = $data;
        return $this;
    }

    public function fail($msg = 'failed', $data = null)
    {
        $this->status = false;
        $this->msg = $msg;
        $this->data = $data;
        return $this;
    }

    public function isValid()
    {
        return $this->status == true;
    }

    public function isSuccess()
    {
        return $this->status == true;
    }

    public function isFail()
    {
        return $this->status != true;
    }

    public function getMessage()
    {
        return $this->msg;
    }

    public function getData()
    {
        return $this->data;
    }

    public function get($name, $default = null)
    {
        return isset($this->data[$name]) ? $this->data[$name] : $default;
    }

    public function __get($name)
    {
        return $this->get($name);
    }
}