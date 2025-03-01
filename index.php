<?php
//$initTime = microtime(true);

if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
    ini_set('zlib.output_compression_level', 9);
    ob_start("ob_gzhandler");
} else
    ob_start();

//Cargar las clases de system solo si se necesitan
spl_autoload_register(function ($class_name) {
    $class_name = strtolower($class_name);
    if (file_exists($fileName = "system/{$class_name}.php"))
        require_once $fileName;
});

//true/false si la petición se hizo con XMLHTTPRequest o no
$rw = @$_SERVER['HTTP_X_REQUESTED_WITH'];
define('IS_AJAX_CALL', !empty($rw) && strtolower($rw) == 'xmlhttprequest');

include('system/common.php');
@include('application/_start_/hook.php');

App::useLocalFolderInURL(true);

if (class_exists('StartHook')) {
    StartHook::exec();

    if (App::$exit || defined('APP_EXIT'))
        return;
}

if (App::$FOLDER) {
    App::$FOLDER = trim(App::$FOLDER, '/') . '/';

    define('APP_DIR', 'application/' . App::$FOLDER);
} else
    define('APP_DIR', 'application/');

$configPath = APP_DIR . 'config/';
if (!is_dir($configPath) && App::$FOLDER)
    $configPath = 'application/config/';

define('CONFIG_PATH', $configPath);

include($configPath . 'config.php');

//Cargar las librerías configuradas en autoload
include_once($configPath . 'autoload.php');
if (isset($autoload)) {
    foreach ($autoload as $type => $val) {
        $values = explode(',', $val);
        foreach ($values as $file)
            include_once("system/{$type}/{$file}.php");
    }
}

$path = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';

/** Constantes de la aplicación **/
//Directorio donde está la aplicación dentro del dominio (ruta local)
define('BASE_PATH', $path . (App::useLocalFolderInURL() ? App::$FOLDER : ''));
//Ruta completa de la aplicación (local)
define('APP_ROOT', str_replace('\\', '/', dirname(__FILE__) . '/'));
//URL con el dominio de la aplicación
define('APP_HOST', '//' . $_SERVER['HTTP_HOST'] . $path);
//Igual a APP_HOST, incluyendo también la carpeta a donde se ha redireccionado la aplicación
define('APP_DOMAIN', '//' . $_SERVER['HTTP_HOST'] . BASE_PATH);
//URL de la carpeta pública
define('PUBLIC_FOLDER', APP_HOST . trim(Config::$publicRoot, '/') . '/');

if (!App::$SEGMENTS)
    App::$SEGMENTS = getSegments($_SERVER['REQUEST_URI']);

//Obtener la función solicitada o la función por defecto
$curFn = strtolower(App::$SEGMENTS[1]);

if (IS_AJAX_CALL) {
    $curFn = Config::$XHR_prefix . $curFn; //El método debe comenzar con el prefijo AppConfig::$XHR_prefix
    noCache();
} else {
    //La petición no es vía AJAX, no se puede solicitar una función que comience con el prefijo para peticiones AJAX
    //ni con el prefijo privado, en caso contrario se ejecuta la función por defecto.
    if (substr($curFn, 0, strlen(Config::$private_prefix)) == Config::$private_prefix ||
        substr($curFn, 0, strlen(Config::$XHR_prefix)) == Config::$XHR_prefix
    )
        $curFn = Config::$defaultFunction;
}

if (!App::$CONTROLLER)
    App::$CONTROLLER = App::$SEGMENTS[0];
if (!App::$FUNCTION)
    App::$FUNCTION = $curFn;
if (!App::$PARAMS)
    App::$PARAMS = array_map(function ($p) {
        return urldecode($p);
    }, array_slice(App::$SEGMENTS, 2));

//Se puede cambiar los valores de App::$CUR_CONTROLLER, App::$CUR_FUNCTION y App::$PARAMS en los hooks
//pre_system y database_success
Hooks::loadHooks('pre_system');

//Comprobar si el idioma es válido
if (!is_dir(APP_ROOT . 'languages/' . Config::$language)) {
    sysError::exception(sprintf('El idioma %s no es válido', Config::$language), APP_DIR . 'config.json', '', null);
    return;
}

$ctrlLower = strtolower(App::$CONTROLLER);
$class = ucfirst($ctrlLower);
$ctrlFile = APP_DIR . trim(Config::$controllersRoot, '/') . "/{$ctrlLower}.php";

if (!class_exists($class, FALSE) && file_exists($ctrlFile)) {
    include_once($ctrlFile);

    if (class_exists($class, FALSE)) {
        try {
            Hooks::loadHooks('pre_constructor');

            App::$instance = new $class();

            Hooks::loadHooks('post_constructor');

            if (method_exists(App::$instance, App::$FUNCTION)) {
                unset($ctrlLower, $ctrlFile);

                Hooks::loadHooks('pre_controller');

                ob_start();
                call_user_func_array([App::$instance, App::$FUNCTION], App::$PARAMS);

                if (App::$exit)
                    die;

                Hooks::loadHooks('post_controller');

                if (!Hooks::loadHooks('display_override'))
                    ob_end_flush();
            } else {
                sysError::methodNotFound(App::$FUNCTION, $ctrlFile);
                return;
            }
        } catch (Exception $e) {
            sysError::exception($e->getMessage(), $e->getFile(), $e->getLine(), $e->getTrace());
            return;
        }
    } else {
        sysError::classNotFound($class, $ctrlFile);
        return;
    }
} else {
    sysError::controllerNotFound($ctrlFile);
    return;
}

Hooks::loadHooks('post_system');

ob_flush();

//echo $deltaTime = microtime(true) - $initTime;