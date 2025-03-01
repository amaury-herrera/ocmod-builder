<?php

class Post {
    public static function __callStatic($key, $args) {
        if (($c = count($args)) == 0)
            return isset($_POST[$key]) ? $_POST[$key] : null;

        if ($c == 1)
            $_POST[$key] = $args[0];
        else
            $_POST[$key] = $args;
    }
}