<?php

class DBConfig {
    public static $driver = 'mysqli';        //Driver de la base de datos. Coincide con el nombre de las carpetas en system/db/drivers
    public static $host = 'localhost';
    public static $port = '3306';
    public static $name = 'simet';
    public static $user = 'root';
    public static $password = ''; //'cm9vdA==';    //c2ltZXQqMjAxMCo= (Para TinoRed)
    public static $persistent = false;
    public static $charset = 'utf8';         //Usar vacío ('') para utilizar el charset por defecto

    public static $results_type = 'object';  //Define el tipo por defecto de los resultados: object|assoc|row

    public static $use_stored_procs = true;  //Define si se utilizan procedimientos almacenados. Si es true, se debe definir los nombres y parámetros en application\config\storedProcedures.php
}