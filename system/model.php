<?php

class MODEL {
    private static $instances = array();

    public static function __callStatic($fn, $args) {
        if (array_key_exists($fnl = strtolower($fn), self::$instances))
            return self::$instances[$fnl];

        $ok = file_exists($file = (APP_DIR . 'models/' . $fnl . '.php'));
        if (!$ok && App::$FOLDER)
            $ok = file_exists($file = ('application/models/' . $fnl . '.php'));

        if ($ok) {
            include_once($file);

            if (class_exists($class = ($fn . 'Model')))
                return self::$instances[$fnl] = new $class();

            throw new Exception('No existe la clase ' . $class);
        }

        throw new Exception('No existe el archivo ' . $file);
    }
}