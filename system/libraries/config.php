<?php

class ConfigManager implements Iterator, ArrayAccess {
    private $data = [];
    private $file;

    function __construct($dataFile) {
        if (file_exists($this->file = $dataFile)) {
            $config = json_decode(file_get_contents($dataFile), true);
            if (!empty($config) && is_array($config))
                $this->data = $config;
        }
    }

    /*
     * Elimina llaves de la configuración
     */
    public function exclude() {
        foreach (func_get_args() as $value) {
            if (is_string($value))
                unset($this->data[$value]);
        }
    }

    protected function tempName() {
        return dirname($this->file) . DIRECTORY_SEPARATOR . uniqid(rand(), true) . '.' . rand() . ".php";
    }

    public function update() {
        //        return file_put_contents($this->file, json_encode($this->data)) !== false;

        if ($f = fopen($this->file, 'wb')) {
            flock($f, LOCK_EX);
            $ok = fwrite($f, json_encode($this->data, JSON_PRETTY_PRINT)) !== false;
            flock($f, LOCK_UN);
            fclose($f);
            return $ok;
        }

        return false;
    }

    public function exists($varName) {
        return array_key_exists($varName, $this->data);
    }

    public function getKeys() {
        return array_keys($this->data);
    }

    public function __get($what) {
        return $this->exists($what) ? $this->data[$what] : null;
    }

    public function __set($what, $value) {
        $this->data[$what] = $value;
    }

    //Iterator
    public function rewind() {
        reset($this->data);
    }

    public function current() {
        return current($this->data);
    }

    public function key() {
        return key($this->data);
    }

    public function next() {
        return next($this->data);
    }

    public function valid() {
        return !is_null($this->key());
    }

    //ArrayAccess
    public function offsetExists($offset) {
        return $this->exists($offset);
    }

    public function offsetGet($offset) {
        return $this->exists($offset) ? $this->data[$offset] : null;
    }

    public function offsetSet($offset, $value) {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }
}