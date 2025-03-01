<?php
function getSegments($url, $basePath = '') {
    if (!$basePath)
        $basePath = defined('BASE_PATH') ? BASE_PATH : '';

    $uri = substr($url, strlen($basePath));
    if (strstr($uri, '?'))
        $uri = substr($uri, 0, strpos($uri, '?'));
    $uri = trim($uri, '/');

    $segments = [];
    foreach (explode('/', $uri) as $i => $route)
        if (trim($route) != '' && ($i > 0 || $route != 'index.php'))
            $segments[] = $route;

    if (class_exists('Config')) {
        $c = count($segments);
        if ($c == 0)
            $segments = [Config::$defaultController, Config::$defaultFunction];
        elseif ($c == 1)
            $segments[] = Config::$defaultFunction;
    }

    return $segments;
}

/**
 * Obtiene una instancia del controlador especificado, permitiendo reutilizar código incluido en un controlador
 * diferente al solicitado
 * @param $controller
 * Nombre del controlador. Si comienza con ~, se buscará en application/controllers. Si contiene /, se buscará
 * en la ruta especificada. En otro caso, se buscará en la carpeta indicada por Config::$controllersRoot dentro de
 * la carpeta APP_DIR
 * El archivo se buscará con el nombre en minúsculas.
 * La clase debe comenzar con letra inicial mayúscula.
 * @return object|null
 */
function getController($controller) {
    if (strpos($controller, '~') === 0) {
        if (!($ctrlLower = strtolower(trim(substr($controller, 1)))))
            return null;

        $path = APP_DIR . 'controllers';
    } else
        if (strpos($controller, '/') === false) {
            if (!($ctrlLower = strtolower($controller)))
                return null;

            $path = APP_DIR . trim(Config::$controllersRoot, '/');
        } else {
            $dirs = explode('/', $controller);
            if (!($ctrlLower = strtolower($dirs[count($dirs) - 1])))
                return null;

            array_pop($dirs);
            $path = trim(implode('/', $dirs), '/');
        }

    if (class_exists($class = ucfirst($ctrlLower)))
        return new $class();

    if (file_exists($file = $path . "/{$ctrlLower}.php")) {
        include_once($file);

        if (class_exists($class))
            try {
                return new $class();
            } catch (Exception $e) {
                sysError::exception($e->getMessage(), $e->getFile(), $e->getLine(), $e->getTrace());
                die;
            }
    }

    return null;
}