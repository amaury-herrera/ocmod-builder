<?php

class Files {
    public static function __callStatic($key, $args) {
        if (($c = count($args)) == 0)
            return isset($_FILES[$key]) ? $_FILES[$key] : null;

        if ($c == 1)
            $_FILES[$key] = $args[0];
        else
            $_FILES[$key] = $args;
    }
}