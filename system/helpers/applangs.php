<?php

class AppLangs {
    public static $lang = 'es';
    private static $langData;

    public static function loadLang($lang) {
        self::$lang = $lang;

        include('languages/' . $lang . '/langDefs.php');

        self::$langData = isset($langDef) ? $langDef : [];
    }

    public static function __callStatic($name, $arguments) {
        if (isset(self::$langData[$name])) {

            if (is_callable($v = self::$langData[$name]) && gettype($v) != 'string')
                return call_user_func_array($v, $arguments);

            return self::$langData[$name];
        }

        return null;
    }
}