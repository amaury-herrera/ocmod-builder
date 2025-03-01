<?php

class App {
    public static $CONTROLLER;
    public static $FUNCTION;
    public static $SEGMENTS;
    public static $PARAMS = array();

    /**
     *La aplicación siempre debe residir en /application o una ruta dentro de /application. Si no está en su raíz, se debe especificar
     *la carpeta o ruta mediante App::$FOLDER = 'ruta/de/la/carpeta'. Ejemplo: App::$FOLDER = 'admin'
     *Cambiar el valor de App::$FOLDER solo es útil si se hace dentro de StartHook::exec(), en application/_start_/hook.php (opcional)
     */
    public static $FOLDER = '';

    public static $instance = null; //Instancia del controlador actual
    public static $exit = FALSE;    //Si se pone en true, al salir de un hook o controlador, sale inmediatamente de la aplicación

    private static $userVars = array();

    public static function __callStatic($key, $args) {
        if (($c = count($args)) == 0)
            return isset(self::$userVars[$key]) ? self::$userVars[$key] : null;

        self::$userVars[$key] = $c == 1 ? $args[0] : $args;
    }

    public static function REQUEST_URL() {
        return APP_DOMAIN . self::$CONTROLLER . '/' . self::$FUNCTION;
    }
} 