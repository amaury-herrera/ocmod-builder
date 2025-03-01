<?php
if (/*!(bool)ini_get('session.auto_start') && */
    session_status() == PHP_SESSION_NONE)
    session_start();

class Session {
    private static $keepFlash = false;

    /*
     * Permite obtener o asignar un valor a una llave de la sesión
     * Ej: Session::currentUser(), Session::currentUser('Pepe')
     */
    public static function __callStatic($key, $args) {
        if (($c = count($args)) == 0)
            return isset($_SESSION[$key]) ? $_SESSION[$key] : null;

        $_SESSION[$key] = $c == 1 ? $args[0] : $args;
    }

    /*
     * Añade/Sustituye las llaves con sus valores en la sesión. Las llaves que comiencen con ~ serán tratadas como
     * llaves flash
     */
    public static function MERGE(array $values) {
        if ($values) {
            foreach ($values as $k => $v)
                if ($k) {
                    if ($k[0] == '~')
                        self::SETFLASH(substr($k, 1), $v);
                    else
                        $_SESSION[$k] = $v;
                }
        }
    }

    /*
     * Elimina una o varias llaves de la sesión
     */
    public static function DELETE(/*$key, ...*/) {
        foreach (func_get_args() as $key)
            unset($_SESSION[$key]);
    }

    /*
     * Destruye los datos almacenados en la sesión
     */
    public static function DESTROY() {
        $_SESSION = [];
    }

    /*
     * Añade una llave a la sesión que será eliminada en la próxima petición. Es útil cuando se desea dejar
     * algún mensaje cuando se redirecciona
     */
    public static function SETFLASH($key, $value) {
        if (isset($_SESSION['$$_FLASH_$$']))
            $_SESSION['$$_FLASH_$$'][$key] = 0;
        else
            $_SESSION['$$_FLASH_$$'] = [$key => 0];

        $_SESSION[$key] = $value;
    }

    public static function keepFlashValues() {
        self::$keepFlash = true;
    }

    /*
     * Destruye las llaves flash
     */
    public function __destruct() {
        static $destroyed = false;

        if (self::$keepFlash)
            return;

        if (isset($_SESSION['$$_FLASH_$$']) && !$destroyed) {
            $destroyed = true;

            foreach ($_SESSION['$$_FLASH_$$'] as $key => &$value) {
                if ($value++)
                    unset($_SESSION['$$_FLASH_$$'][$key], $_SESSION[$key]);
            }
        }
    }
}

//Lograr que se ejecute el destructor de Session
$GLOBALS['_S_E_S_'] = new Session();