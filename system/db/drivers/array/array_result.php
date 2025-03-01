<?php

class arrayResult extends DB_result {
    private $result = array();

    public function __construct(array $result) {
        $this->setResultType(DBConfig::$results_type);

        if (!is_null($result))
            $this->result = $result;
    }

    public function free_result() {
        $this->result = null;
    }

    public function valid() {
        if ($this->key < count($this->result)) {
            $this->_current = $this->{$this->resultFn}();
            return true;
        }

        return false;
    }

    public function num_rows() {
        return count($this->result);
    }

    public function fetch_assoc() {
        return $this->result[$this->key];
    }

    public function fetch_object() {
        $obj = new stdClass();
        foreach ($this->result[$this->key] as $i => $v) {
            if (!is_numeric($i))
                $obj->$i = $v;
        }

        return $obj;
    }

    public function fetch_row() {
        return ($res = $this->result[$this->key]) === false ? false : array_values($res);
    }

    public function seek($offset) {
        if ($offset < count($this->result)) {
            $this->key = $offset;
            return true;
        }

        return false;
    }

    public function more_results() {
        return false;
    }

    public function __sleep() {
        return array('result', 'resultFn');
    }
}