<?php

class Get {
    public static function __callStatic($key, $args) {
        if (($c = count($args)) == 0)
            return isset($_GET[$key]) ? $_GET[$key] : null;

        if ($c == 1)
            $_GET[$key] = $args[0];
        else
            $_GET[$key] = $args;
    }
}