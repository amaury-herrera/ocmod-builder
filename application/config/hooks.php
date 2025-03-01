<?php
/*
 * Valores válidos              Se ejecuta...
 * -----------------------------------------------------------------------------------------------------
 *  pre_system,                 Ya se han definido las constantes APP_ROOT, APP_DOMAIN, PUBLIC_FOLDER, IS_AJAX_CALL y App::$CUR_CONTROLLER, App::$CUR_FUNCTION, App::$SEGMENTS, App::REQUEST_URL()
 *  database_success,           Al conectarse con éxito a la base de datos
 *  database_error,             Al producirse un error
 *  pre_constructor,            Antes de crear la instancia del controlador
 *  post_constructor,           Después de crear la instancia del controlador
 *  pre_controller,             Antes de ejecutar la función del controlador
 *  post_controller             Después de ejecutar la función del controlador
 *  display_override,           Antes de enviar la respuesta al cliente. Debe encargarse de hacerlo con: ob_end_flush();
 *  post_system                 Antes de terminar la aplicación
 *
 *
 * Ejemplo:
 *
 *  $hooks = array(
 *      'pre_controller' => array(
 *        array(
 *          'file' => 'hooks/check_access.php',   //Ruta y nombre del archivo
 *          'class' => 'CheckAccess',             //Nombre de la clase a instanciar
 *          'function' => 'index',                //Nombre del método/función a ejecutar
 *          'params' => array()                   //Parámetros. Opcional
 *        ),
 *        ...
 *      ),
 *      ...
 *  );
 */

$hooks = [
    'pre_system' => [
        [
            'file' => 'controllers/hooks/pre_system.php',
            'class' => 'ConfigCheck',
            'function' => 'check',
        ]
    ],
];