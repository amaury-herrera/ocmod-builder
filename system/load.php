<?php

class Load {
    public static function Helper($name) {
        include_once("system/helpers/{$name}.php");
    }

    public static function Library($name) {
        include_once("system/libraries/{$name}.php");
    }
}