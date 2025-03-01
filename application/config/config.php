<?php

class Config {
    public static $language = 'es'; //Idioma
    public static $charset = 'utf-8'; //Charset de la página

    public static $XHR_prefix = 'ajax_';  //Las funciones a ejecutar vía AJAX, deben comenzar con este prefijo
    public static $private_prefix = '__'; //Las funciones que comiencen con este prefijo no pueden utilizarse en peticiones

    public static $defaultController = 'main'; //Controlador por defecto
    public static $defaultFunction = 'index';  //Función por defecto

    //Layouts
    public static $defaultLayout = 'default'; //Layout por defecto
    public static $skipDefaultLayout = false; //Poner a TRUE si una vista no utiliza layout y no se desea utilizar el layout por defecto

    //Raíz de las carpetas. Puede cambiarse según la estructura que se desee o desde un hook pre_system o pre_controller
    public static $publicRoot = 'public'; //Carpeta de archivos públicos (css, js, imágenes). Relativa a la raíz.
    public static $controllersRoot = 'controllers';
    public static $viewsRoot = 'views';

    public static $layoutsFolder = '';  //Dejar vacío o especificar una ruta dentro de views/layouts. Ejemplo: level0
    public static $partialsFolder = ''; //Dejar vacío o especificar una ruta dentro de views/partials. Ejemplo: level0
}