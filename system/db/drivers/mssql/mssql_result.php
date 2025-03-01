<?php

class mssqlResult extends DB_result {
    private $mysqli;

    public function __construct(mysqli $mysqli, $results) {
        parent::__construct($results);
        $this->mysqli = $mysqli;
    }

    public function free_result() {
        if (is_resource($this->curResult)) {
            @mssql_free_result($this->curResult);
            $this->curResult = null;
        }
    }

    public function num_rows() {
        return $this->curResult->num_rows;
    }

    public function fetch_assoc() {
        return $this->curResult->fetch_assoc();
    }

    public function fetch_object() {
        return $this->curResult->fetch_object();
    }

    public function fetch_row() {
        return $this->curResult->fetch_row();
    }

    public function seek($offset) {
        if ($this->curResult) {
            $res = $this->curResult->data_seek($offset);
            if ($res === true)
                $this->key = $offset;

            return $res;
        }

        return false;
    }
}