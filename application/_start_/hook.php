<?php
//ini_set("display_errors", "0");
ini_set("memory_limit", -1);
set_time_limit(0);

class StartHook {
    /*
     * Se ejecuta antes que nada.
     * Cambia las rutas con App::$FOLDER = '<directorio dentro de application>';
     */
    public static function exec() {
        App::useLocalFolderInURL(false);
        //        App::$FOLDER = 'login';
    }
}