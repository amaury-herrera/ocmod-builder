<?php

class sysError {
    public static function Code($code) {
        App::errorCode($code);
        if (file_exists($file = "system/errors/{$code}.php"))
            include($file);
        else
            echo "<hmtl><head><title>Error {$code}</title></head><body><h1>Error {$code}</h1></body></hmtl>";
    }

    public static function controllerNotFound($filename = null) {
        include('system/errors/internal/controllerNotFound.php');
    }

    public static function classNotFound($description = null, $filename = null) {
        include('system/errors/internal/classNotFound.php');
    }

    public static function methodNotFound($description = null, $filename = null) {
        include('system/errors/internal/methodNotFound.php');
    }

    public static function exception($exceptionMessage, $filename, $line, $trace) {
        include('system/errors/internal/exception.php');
    }

    public static function notFound($exitApp) {
        App::errorCode(404);
        header("HTTP/1.0 404 Not Found");

        if ($exitApp)
            App::$exit = true;
    }

    public static function accessDenied($exitApp) {
        App::errorCode(403);
        header('HTTP/1.0 403 Forbidden');

        if (!IS_AJAX_CALL)
            empty(ErrorConfig::$_403_layout) ? self::Code(403) : Views::Render(/*'~' . */ trim(ErrorConfig::$viewsPath, '/') . '/403');

        if ($exitApp)
            App::$exit = true;
    }
}