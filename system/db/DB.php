<?php
define('sqlTEXT', 35);
define('sqlCHAR', 47);
define('sqlVARCHAR', 39);
define('sqlINT1', 48);
define('sqlINT2', 52);
define('sqlINT4', 56);
define('sqlBIT', 50);
define('sqlFLT4', 59);
define('sqlFLT8', 62);
define('sqlFLTN', 109);

class DB {
    private static $instance = null;

    private static $connections = array('default' => null);
    private static $currentConnection = 'default';

    public static function loadDriver($driver) {
        if (is_dir($drv = 'system/db/drivers/' . $driver)) {
            $drv .= '/' . $driver;

            include_once($drv . '_driver.php');
            include_once($drv . '_result.php');

            return true;
        }

        return false;
    }

    /**
     * Inicializa la conexión a la base de datos configurada
     */
    public static function init() {
        if (self::$currentConnection == 'default' && is_null(self::$instance)) {
            $class = DBConfig::$driver . 'Driver';

            self::$connections['default'] = self::$instance =
                new $class(DBConfig::$host, DBConfig::$name, DBConfig::$port, DBConfig::$user, DBConfig::$password, DBConfig::$charset);
        }
    }

    /**
     * Añade una conexión a una base de datos, usualmente diferente a la configurada
     * @param $name
     * Nombre que se le dará a la conexión
     * @param DB_driver $connection
     * Instancia de DB_Driver a añadir
     * @throws Exception
     */
    public static function addConnection($name, DB_driver $connection, $use = false) {
        if (array_key_exists($name, self::$connections))
            throw new Exception('Ya existe una conexión con el nombre: ' . $name);

        if (is_null($connection))
            throw new Exception('Debe especificar una conexión válida');

        self::$connections[$name] = $connection;

        if ($use)
            self::useConnection($name);
    }

    /**
     * Establece la conexión a la base de datos que se utilizará en lo adelante
     * @param $name
     * Nombre dado a la conexión al añadirla. Para utilizar la conexión configurada se
     * debe establecer $name = 'default'
     * @throws Exception
     */
    public static function useConnection($name) {
        if (!array_key_exists($name, self::$connections))
            throw new Exception('No existe una conexión con el nombre: ' . $name);

        self::$instance = self::$connections[self::$currentConnection = $name];
    }

    /*
     * Devuelve la instancia del driver para la conexión activa
     */
    public static function getConnection() {
        return self::$instance;
    }

    /*
     * Devuelve el nombre de la conexión activa
     */
    public static function getConnectionName() {
        return self::$currentConnection;
    }

    /*
     * Funciones que pueden accederse desde DB::xxx()
     */
    private static $__db_exported__ = [
        'connect', 'connected', 'getDatabaseName', 'close', 'escape_str', 'select', 'delete', 'update', 'insert', 'insert_id',
        'query', 'affected_rows', 'multi_query', 'last_query', 'set_last_query', 'error', 'error_message', 'bind',
        'trans_begin', 'trans_start', 'trans_complete', 'trans_status', 'trans_commit', 'trans_rollback'
    ];

    public static function __callStatic($fn, $args) {
        if (array_search($fn, self::$__db_exported__) !== false)
            return call_user_func_array(array(self::$instance, $fn), $args);

        throw new BadMethodCallException("El método {$fn} no existe en la clase DB");
    }
}