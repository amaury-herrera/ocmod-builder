<?php

class _Cache_class {
    
  public function recreate($echo = false){}
  
  public function recreateIfNeeded(){}
  
  public function setUpdateConfig($update){}
  
  public function update($path, callable $callback): bool{
    return true;
  }
  
  public function updateTree(callable $callback){}
  
  public function findLeave($path, &$cache){}
}

class _Diff_class {
    /**
     * Returns the diff between two arrays or strings as array.
     *
     * Each array element contains two elements:
     *   - [0] => mixed $token
     *   - [1] => 2|1|0
     *
     * - 2: REMOVED: $token was removed from $from
     * - 1: ADDED: $token was added to $from
     * - 0: OLD: $token is not changed in $to
     *
     * @param array|string $from
     * @param array|string $to
     */
  public function diffToArray($from, $to): array{
    return [];
  }
  
  public function calculate(array $from, array $to): array{
    return [];
  }
}

class _Files_class {
    
  public function normalizePath($path, $trim = true): string{
    return "";
  }
  
  public function getUploadFiles(&$list, $path){}
  
  public function getTree(){}
  
  public function getFileList($path){}
  
  public function newDir($relPath, $folderName){}
  
  public function delDir($relPath){}
  
  public function delTree($dir, $delRoot = false){}
  
  public function renDir($relPath, $newName){}
  
  public function renameFile($relPath, $filename, $newName){}
  
  public function deleteFile($relPath, $filename){}
}

class _OCMOD_class {
  public $errors = [];
  
  public function getXML(){}
  
  public function getErrors(){}
  /**
     * Procesa el contenido de un archivo en busca de bloques OCMOD.
     * @param $text
     * Contenido del archivo a procesar
     * @param $fileName
     * Nombre del archivo (solo para incluir en el texto de los errores)
     * @return bool
     * Devuelve false si no se encuentra ningún bloque OCMOD y true si contiene al menos un bloque, sean o no válidos.
     * Si hay errores devuelve true y se reflejan en MODEL::OCMOD()->errors.
     */
  public function processContent($text, $fileName): bool{
    return true;
  }
  
  public function processFile($fileName): bool{
    return true;
  }
  
  public function generateXML(){}
  
  public function createZip($zipFilename = ''){}
}


  class MODEL {
    public static function Cache() {
      return new _Cache_class();
    }
    public static function Diff() {
      return new _Diff_class();
    }
    public static function Files() {
      return new _Files_class();
    }
    public static function OCMOD() {
      return new _OCMOD_class();
    }
  }

class FormValidation {
  public function __construct($formName = 'default', array $rules = null, $overridePostValues = null) {
  }
  
  public static function form(array $rules = null, $overridePostValues = null) {
    return new FormValidation();
  }
  
  public function __call($fn, $args) {
    return new FormValidation();
  }
  
  public static function Error($formField, $return = false) {
    return '';
  }
  
  public static function hasError($formField) {
    return true;
  }
  
  public static function hasErrors($formName = '') {
    return true;
  }
  
  public static function getSummary($formName = '', array $tpl = array()) {
    return '';
  }
  
  public function clearError($formField) {}
  
  public function setError($formField, $error) {}
  
  public function setLabelsEnclosingTags($open, $close) {}
  
  
  public function run() {
    return true;
  }
  
  public function trim() {
    return new FormValidation();
  }
  
  public function required() {
    return new FormValidation();
  }
  
  public function if_set() {
    return new FormValidation();
  }
  
  public function regex_match($regex, $msg = null) {
    return new FormValidation();
  }
  
  public function matches($field, $label = null) {
    return new FormValidation();
  }
  
  public function password_match($current_pw) {
    return new FormValidation();
  }
  
  public function in_list(array $values) {
    return new FormValidation();
  }
  
  public function between($a, $b) {
    return new FormValidation();
  }
  
  public function is_unique($field) {
    return new FormValidation();
  }
  
  public function min_length($val) {
    return new FormValidation();
  }
  
  public function max_length($val) {
    return new FormValidation();
  }
  
  public function exact_length($val) {
    return new FormValidation();
  }
  
  public function valid_url() {
    return new FormValidation();
  }
  
  public function valid_email() {
    return new FormValidation();
  }
  
  public function valid_emails() {
    return new FormValidation();
  }
  
  public function valid_date() {
    return new FormValidation();
  }
  
  public function valid_ip() {
    return new FormValidation();
  }
  
  public function valid_ipv4() {
    return new FormValidation();
  }
  
  public function valid_ipv6() {
    return new FormValidation();
  }
  
  public function alpha($tildes = false, $spaces = false) {
    return new FormValidation();
  }
  
  public function alpha_numeric($tildes = false, $spaces = false) {
    return new FormValidation();
  }
  
  public function alpha_dash() {
    return new FormValidation();
  }
  
  public function numeric() {
    return new FormValidation();
  }
  
  public function is_numeric() {
    return new FormValidation();
  }
  
  public function integer() {
    return new FormValidation();
  }
  
  public function decimal() {
    return new FormValidation();
  }
  
  public function greater_than($min) {
    return new FormValidation();
  }
  
  public function less_than($max) {
    return new FormValidation();
  }
  
  public function valid_base64() {
    return new FormValidation();
  }
  
  public function is_natural_no_zero() {
    return new FormValidation();
  }
  
  public function is_natural() {
    return new FormValidation();
  }
  
  public function only_chars($chars) {
    return new FormValidation();
  }
  
  public function not_contains($chars) {
    return new FormValidation();
  }
}

class DB_result implements Iterator {
    public function free_result() {}

    public function fetch_object() {}

    public function fetch_assoc() {}

    public function fetch_row() {}

    public function seek($offset) {}

    public function num_rows() {}

    public function next() {}

    public function valid() {}

    public function key() {}

    public function current() {}

    public function rewind() {}

    /**
     * Devuelve la fila actual en el formato especificado (object|assoc|array|row).
     * Devuelve false cuando se alcanzó el final
     * @param null $callback
     * Puede ser un callable que procese el resultado o un array indicando el tipo (valor) del campo (llave) al que debe ser convertido
     * @return bool|object|array
     */
    public function row($callback = null) {}

    /**
     * Devuelve un array con todas las filas en el formato especificado (object|assoc|array|row).
     * @param null $callback
     * Puede ser un callable que procese el resultado o un array indicando el tipo (valor) del campo (llave) al que debe ser convertido
     * @return array
     */
    public function rows($callback = null) {}

    //Obtiene en un array $limit filas desde la posicion $from, utilizando como llave(s)
    //la(s) columna(s) especificadas en $keyCols (separadas por comas). Si se devuelven dos columnas
    //se asigna el valor directamente a la entrada, de lo contrario se devuelve como array.
    //Si la consulta devuelve más de una fila con el mismo valor en la combinación de llaves
    //se retornará agrupado
    public function rowsByKey($keyCols, $from = 0, $limit = -1, $alwaysArray = false) {}

    public function setResultType($type) {}

    public function next_result() {}

    public function more_results() {}
}

class insert_class {
  public function into($table, $fields) {
    return $this;
  }

  public function values($values) {
    return $this;
  }

  /**
   * @return bool
   */
  public function exec($resultType = null) {
    return true;
  }
  
  public function get_query() {
    return '';
  }
}

class delete_class {
  public function where($condition, $bindings = null) {
    return $this;
  }

  public function from($tableName) {
    return $this;
  }

  /**
   * @return bool
   */
  public function exec($resultType = null) {
    return true;
  }
  
  public function get_query() {
    return '';
  }
}

class update_class {
  public function where($condition, $bindings = null) {
    return $this;
  }

  public function set($set, $bindings = null) {
    return $this;
  }

  /**
   * @return bool
   */
  public function exec($resultType = null) {
    return true;
  }
  
  public function get_query() {
    return '';
  }
}

class select_class {
  public function from($tableName) {
    return $this;
  }

  public function where($condition, $bindings = null) {
    return $this;
  }

  public function having($condition, $bindings = null) {
    return $this;
  }

  public function group_by($fields) {
    return $this;
  }

  public function order_by($fields) {
    return $this;
  }

  public function limit($count, $offset = null) {
    return $this;
  }

  public function join($tableName, $condition, $bindings = null) {
    return $this;
  }

  public function left_join($tableName, $condition, $bindings = null) {
    return $this;
  }

  public function right_join($tableName, $condition, $bindings = null) {
    return $this;
  }

  public function cross_join($tableName, $condition, $bindings = null) {
    return $this;
  }

  /**
   * @return DB_result
   */
  public function get($resultType = null) {
    return new DB_result();
  }

  /**
   * @return DB_result
   */
  public function exec($resultType = null) {
    return new DB_result();
  }
  
  public function get_query() {
    return '';
  }
}

class DB {
  /**
   * Permite conectarse a la base de datos
   * @param $persistent
   * Define si la conexión debe ser persistente o no
   * @return bool
   * True si se conectó con éxito al servidor.
   * False en caso contrario
   */
  public static function connect($persistent) {}

  /**
   * Devuelve true si se ha conectado con éxito a la base de datos configurada
   * @return bool
   */
  public static function connected() {}
  
  public static function getDatabaseName() { return '';}

  /**
   * Cierra la conexión actual
   * @return bool
   */
  public static function close() {}

  /**
   * Permite escapar valores utilizados en consultas y evitar SQL Injections
   * @param $str
   * Valor a escapar
   * @return mixed
   * Devuelve la cadena escapada
   */
  public static function escape_str($str) { return ''; }

  /**
   * Inicia una consulta SELECT
   * @param string $fields
   * Campos a devolver. Por defecto devuelve todos los campos (*).
   * @return select_class
   * Instancia del driver configurado
   */
  public static function select($fields = '*') {
    return new select_class();
  }

  /**
   * Inicia una consulta DELETE
   * @return delete_class
   * Instancia del driver configurado
   */
  public static function delete() {
    return new delete_class();
  }

  /**
   * Inicia una consulta UPDATE
   * @param $table
   * Nombre de la tabla a actualizar
   * @return update_class
   * Instancia del driver configurado
   */
  public static function update($table) {
    return new update_class();
  }

  /**
   * Inicia una consulta INSERT
   * @return insert_class
   * Instancia del driver configurado
   */
  public static function insert() {
    return new insert_class();
  }

  /**
   * Devuelve el último id generado en una consulta INSERT
   * @return mixed
   */
  public static function insert_id() {}

  /**
   * Devuelve el último id generado en una consulta INSERT
   * @return DB_Result|bool
   */
  public static function query($sql, $bindings = null) {}

  /**
   * Devuelve el número de filas afectadas por la última consulta UPDATE, DELETE o INSERT
   * @return mixed
   */
  public static function affected_rows() {}

  /**
   * Ejecuta una o varias consultas
   * @param $sql
   * Consulta(s) a ejecutar
   * @param null $bindings
   * Opcional. Valores con los cuales se sustituirá el caracter ?
   * @return DB_Result
   * El resultado se obtiene solo para la primera consulta.
   * Devuelve DB_Result para las consultas que devuelven un conjunto de datos. True/False para otras consultas
   */
  public static function multi_query($sql, $bindings = null) {}

  public static function last_query() { return ''; }

  /**
   * Asigna el valor de la última consulta ejecutada. Solo para uso interno.
   * @param $q
   * Consulta a establecer como última
   */
  public static function set_last_query($q) {}

  /**
   * Devuelve el código del último mensaje de error
   * @return mixed
   */
  public static function error() { return 0; }

  /**
   * Devuelve un texto descriptivo del último mensaje de error
   * @return mixed
   */
  public static function error_message() { return ''; }

  /**
   * Sustituye los caracteres ? en $sql por el valor escapado correspondiente en $binds
   * @return string
   */
  public static function bind($sql, $binds) {}

  /**
   * Comienza una transacción
   * @return bool
   */
  public static function trans_begin() {}

  /**
   * Start Transaction
   * @return  void
   */
  public static function trans_start() {}

  /**
   * Complete Transaction
   * @return  bool
   */
  public static function trans_complete() {
  }

  /**
   * Devuelve true/false si la transacción se ha ejecutado correctamente o no
   * @return  bool
   */
  public static function trans_status() {}

  /**
   * Hace permanentes los cambios de una transacción
   * @return bool
   */
  public static function trans_commit() {}

  /**
   * Deshace las operaciones realizadas durante una transacción
   * @return bool
   */
  public static function trans_rollback() {}
}
