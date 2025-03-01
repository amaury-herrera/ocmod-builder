<?php

class Hooks {
    private static $hooks = array();

    public static function Init() {
        @include_once(CONFIG_PATH . 'hooks.php');

        if (isset($hooks))
            self::$hooks = $hooks;
    }

    /*
     * Ejecuta los hooks según el tipo especificado.
     * Devuelve true si existe al menos un hook de este tipo
     */
    public static function loadHooks($type) {
        if (isset(self::$hooks[$type])) {
            try {
                foreach (self::$hooks[$type] as $hook) {
                    try {
                        if (file_exists($file = APP_DIR . trim($hook['file'], '/'))) {
                            include_once($file);

                            if (class_exists($class = $hook['class'])) {
                                if (method_exists($inst = new $class(), $fn = $hook['function'])) {
                                    if (isset($hook['params']))
                                        call_user_func_array(array($inst, $fn), $hook['params']);
                                    else
                                        $inst->$fn();

                                    continue;
                                } else
                                    sysError::methodNotFound($fn, $file);
                            } else
                                sysError::classNotFound($class, $file);
                        } else
                            sysError::controllerNotFound($file);
                    } catch (Exception $e) {
                        sysError::exception($e->getMessage(), $e->getFile(), $e->getLine(), $e->getTrace());
                    }

                    die;
                }

                if (App::$exit)
                    die;
            } finally {
                return true;
            }
        }

        return false;
    }
}

Hooks::Init();