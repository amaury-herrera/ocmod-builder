<?php

abstract class DB_driver {
    protected $params;
    protected $conn;
    protected $connected = false;

    protected $qParts = [];
    protected $type = null;
    protected $_lastQuery = '';

    protected $_trans_depth = 0;
    protected $_trans_status = true;

    public function __construct($host, $name, $port, $user, $pw, $charset) {
        $this->params = (object)['host' => $host, 'dbname' => $name, 'port' => $port, 'user' => $user, 'pw' => $pw, 'charset' => $charset];
    }

    /**
     * Devuelve true si se ha conectado con éxito a la base de datos configurada
     * @return bool
     */
    public function connected() {
        return $this->connected;
    }

    public function getDatabaseName() {
        return $this->params->dbname;
    }

    public abstract function connect($persistent);

    public abstract function close();

    public abstract function escape_str($str);

    public abstract function error();

    public abstract function error_message();

    public abstract function insert_id();

    public abstract function affected_rows();

    abstract function trans_begin();

    abstract function trans_commit();

    abstract function trans_rollback();

    /**
     * Start Transaction
     * @return  void
     */
    function trans_start() {
        if ($this->_trans_depth > 0) {
            $this->_trans_depth++;
            return;
        }

        $this->_trans_status = true;
        $this->trans_begin();
    }

    /**
     * Complete Transaction
     * @return  bool
     */
    function trans_complete() {
        if ($this->_trans_depth > 1) {
            $this->_trans_depth--;
            return TRUE;
        }

        // Las funciones query() y multi_query() pondrán esta bandera a FALSE si la consulta falla
        if ($this->_trans_status === FALSE) {
            $this->trans_rollback();
            return FALSE;
        }

        $this->trans_commit();
        return TRUE;
    }

    /**
     * Devuelve true/false si la transacción se ha ejecutado correctamente o no
     * @return  bool
     */
    function trans_status() {
        return $this->_trans_status;
    }

    /* ---------------------------------------------------------------------- */

    protected function fixValue($v, $quotes = true) {
        if (is_null($v))
            return 'NULL';

        if ($v === FALSE)
            return 0;

        if (is_string($v))
            return $quotes ? '\'' . $this->escape_str($v) . '\'' : $this->escape_str($v);

        if (is_array($v)) {
            foreach ($v as &$value)
                $value = $this->fixValue($value);

            return implode(',', $v);
        }

        return $v;
    }

    protected function getBindings(array $args, $pos) {
        if (count($args) > $pos) {
            $bindings = $args[$pos];

            if (!is_array($bindings))
                $bindings = array_slice($args, $pos);

            return $bindings;
        }

        return null;
    }

    /**
     * Sustituye los caracteres ? en $sql por el valor escapado correspondiente en $binds.
     * Se puede utilizar de dos formas:
     *    Con nombres de parámetros: ( :nombre )
     *      DB::bind('name = :nombre', ['nombre' => 'Juan']); se obtiene: "name = 'Juan'"
     *      Los nombres de índices deben existir en $binds
     *
     *    Con índices ( ? )
     *      DB::bind('a = ?', [5]); se obtiene "a = 5"
     *      La cantidad de ? en $sql debe corresponderse con la cantidad de elementos en $binds
     *
     * En cualquiera de las dos variantes:
     *    Si se antepone el caracter @ los valores no serán escapados. Si el valor es un array, cada elemento se escapa y se unen separados por comas.
     *      Ej: DB::bind('select * from estudiantes where @:condition', ['condition' => 'edad = 50']); se obtiene: "select * from estudiantes where edad = 50"
     *
     *    Si se antepone el caracter ! los valores no serán escapados. No se aplica si el valor es un array.
     *      Ej: DB::bind('select * from estudiantes where !?', ['edad = 50']); se obtiene: "select * from estudiantes where edad = 50"
     * @param $sql
     * @param $binds
     * @return string
     */
    public function bind($sql, $binds) {
        if (!is_array($binds))
            $binds = [$binds];

        $i = 0;
        $cnt = count($binds);
        $wName = $wIndex = false;
        $sql = preg_replace_callback('/([@!]?)([?]|:[a-z_][a-z_0-9]*)/i', function ($m) use (&$i, &$binds, &$wName, &$wIndex, $cnt) {
            $c = $m[1];
            $m = $m[2];

            if ($m[0] == ':') {
                if ($wIndex)
                    throw new InvalidArgumentException('No debe mezclar parámetros con nombre y de índices en los elementos de enlace');

                $wName = true;
                $ind = substr($m, 1);
            } else {
                if ($wName)
                    throw new InvalidArgumentException('No debe mezclar parámetros con nombre y de índices en los elementos de enlace');

                $wIndex = true;
                $ind = $i++;

                if ($ind > $cnt)
                    return '';
            }

            if (array_key_exists($ind, $binds)) {
                $v = $binds[$ind];
                return $c == '@'
                    ? (is_array($v) ? implode(',', $v) : $v)
                    : $this->fixValue($v, $c != '!');
            }

            if ($wName)
                throw new InvalidArgumentException(sprintf('No existe el índice "%s" en los elementos de enlace', $ind));
        }, $sql);

        if ($wIndex && $i != count($binds))
            throw new InvalidArgumentException(sprintf('Ha especificado %d elementos de enlace, pero se necesitan %d', count($binds), $i));

        return $sql;
    }

    /**
     * Devuelve la última consulta ejecutada
     * @return mixed
     */
    public function last_query() {
        return $this->_lastQuery;
    }

    /**
     * Asigna el valor de la última consulta ejecutada. Solo para uso interno.
     * @param $q
     * Consulta a establecer como última
     */
    public function set_last_query($q) {
        $this->_lastQuery = $q;
    }


    /**
     * Permite iniciar una nueva consulta con métodos encadenados
     */
    public function clearAll() {
        $this->qParts = array();
        $this->type = null;
    }

    /**
     * Inicia una consulta INSERT
     * @return mixed
     * Instancia del driver configurado
     */
    public function insert() {
        $this->clearAll();
        $this->type = 'C';
        return $this;
    }

    /**
     * Inicia una consulta SELECT
     * @param string $fields
     * Campos a devolver. Por defecto devuelve todos los campos (*).
     * @return mixed
     * Instancia del driver configurado
     */
    public function select($fields = '*') {
        $this->clearAll();
        $this->type = 'R';
        $this->qParts['fields'] = ($fields = trim($fields)) ? $fields : '*';
        return $this;
    }

    /**
     * Inicia una consulta UPDATE
     * @param $table
     * Nombre de la tabla a actualizar
     * @return mixed
     * Instancia del driver configurado
     */
    public function update($table) {
        if (!($table = trim($table)))
            throw new InvalidArgumentException('No se ha especificado la tabla a actualizar en la sentencia UPDATE');

        $this->clearAll();
        $this->type = 'U';
        $this->qParts['table'] = trim($table);
        $this->qParts['set'] = null;
        return $this;
    }

    /**
     * Inicia una consulta DELETE
     * @return mixed
     * Instancia del driver configurado
     */
    public function delete() {
        $this->clearAll();
        $this->type = 'D';
        return $this;
    }

    public function set($set/*[, $bindings]*/) {
        if (is_null($bindings = $this->getBindings(func_get_args(), 1)))
            $bindings = [];

        if (is_array($set)) {
            if (!$set)
                throw new Exception('Parámetros no válidos para la cláusula SET de la sentencia UPDATE');

            if (count($set) != count($bindings))
                throw new Exception('No coincide la cantidad de campos con la cantidad de valores en la cláusula SET de la sentencia UPDATE');

            $data = [&$set, &$bindings];
        } else {
            if (!($set = trim($set)))
                throw new Exception('Parámetros no válidos para la cláusula SET de la sentencia UPDATE');

            $data = [[&$set], &$bindings];
        }

        if ($cset = &$this->qParts['set']) {
            $cset[0] = array_merge($cset[0], $data[0]);
            $cset[1] = array_merge($cset[1], $data[1]);
        } else
            $this->qParts['set'] = &$data;

        return $this;
    }

    public function into($table, $fields) {
        if (empty($table))
            throw new Exception('No ha especificado el nombre de la tabla en la cláusula INTO de la sentencia INSERT');

        if (is_string($fields)) {
            if (!($fields = trim($fields)))
                throw new Exception('No ha especificado ningún campo en la cláusula INTO de la sentencia INSERT');

            $fields = explode(',', $fields);
        }

        $fields = array_map('trim', $fields);

        if (empty($fields))
            throw new Exception('Debe especificar al menos un campo en la cláusula INTO de la sentencia INSERT');

        $this->qParts['into'] = array($table, $fields);
        return $this;
    }

    public function values($values) {
        $values = func_get_args();

        if (!array_key_exists('into', $this->qParts))
            throw new Exception('Debe especificar la cláusula INTO antes de la cláusula VALUES');

        $isArray = false;
        $elems = count($this->qParts['into'][1]);
        $i = 0;
        foreach ($values as $v) {
            if (is_array($v)) {
                if ($i == 0)
                    $isArray = true;
                elseif (!$isArray)
                    throw new Exception('Cuando especifica un array para los valores de la sentencia INSERT, todos los elementos tienen que ser array y tener la misma cantidad de elementos');

                if (count($v) != $elems)
                    throw new Exception('No coincide la cantidad de campos con la cantidad de valores en la cláusula VALUES de la sentencia INSERT');

                foreach ($v as $val)
                    if (is_array($val))
                        throw new Exception('No se admiten valores de tipo array en la cláusula VALUES de la sentencia INSERT');
            } else {
                if ($i == 0) {
                    if (count($values) != $elems)
                        throw new Exception('No coincide la cantidad de campos con la cantidad de valores en la cláusula VALUES de la sentencia INSERT');
                } elseif ($isArray)
                    throw new Exception('No puede combinar valores array y no array en la cláusula VALUES de la sentencia INSERT');

                if (is_array($v))
                    throw new Exception('No se admiten valores de tipo array en la cláusula VALUES de la sentencia INSERT');
            }

            $i++;
        }

        if (array_key_exists('values', $this->qParts)) {
            if (!is_array($this->qParts['values'][0]))
                $this->qParts['values'] = array($this->qParts['values']);

            if ($isArray)
                $this->qParts['values'] = array_merge($this->qParts['values'], $values);
            else
                $this->qParts['values'][] = $values;
        } else
            $this->qParts['values'] = $values;

        return $this;
    }

    public function from($tableName) {
        if (!($tableName = trim($tableName)))
            throw new InvalidArgumentException('No se ha especificado la tabla en la cláusula FROM');

        $this->qParts['from'] = $tableName;
        return $this;
    }

    private function doJoin($tableName, $condition, $bindings, $type) {
        if (!($tableName = trim($tableName)))
            throw new InvalidArgumentException('No se ha especificado la tabla en la cláusula JOIN');

        if (!($condition = trim($condition)))
            throw new InvalidArgumentException('No se ha especificado la condición en la cláusula JOIN');

        $bindings = $this->getBindings($bindings, 2);
        $this->qParts['joins'][] = array($tableName, is_null($bindings) ? (string)$condition : $this->bind((string)$condition, $bindings), $type);
        return $this;
    }

    public function join($tableName, $condition/*[, $bindings = null]*/) {
        return $this->doJoin($tableName, $condition, func_get_args(), 'N');
    }

    public function left_join($tableName, $condition/*[, $bindings = null]*/) {
        return $this->doJoin($tableName, $condition, func_get_args(), 'L');
    }

    public function right_join($tableName, $condition/*[, $bindings = null]*/) {
        return $this->doJoin($tableName, $condition, func_get_args(), 'R');
    }

    public function cross_join($tableName, $condition/*,[ $bindings = null]*/) {
        return $this->doJoin($tableName, $condition, func_get_args(), 'C');
    }

    private function doCondition($condition, $bindings, $key) {
        if (!($condition = trim($condition)))
            throw new InvalidArgumentException('No se ha especificado la condición en la cláusula ' . strtoupper($key));

        $this->qParts[$key] = is_null($bindings = $this->getBindings($bindings, 1)) ? (string)$condition : $this->bind((string)$condition, $bindings);
        return $this;
    }

    public function where($condition/*[, $bindings = null]*/) {
        return $this->doCondition($condition, func_get_args(), 'where');
    }

    public function having($condition/*[, $bindings = null]*/) {
        return $this->doCondition($condition, func_get_args(), 'having');
    }

    public function group_by($fields) {
        if (!($fields = trim($fields)))
            throw new InvalidArgumentException('No se ha especificado la condición en la cláusula GROUP BY');

        $this->qParts['group by'] = $fields;
        return $this;
    }

    public function order_by($fields) {
        if (!($fields = trim($fields)))
            throw new InvalidArgumentException('No se ha especificado la condición en la cláusula ORDER BY');

        $this->qParts['order by'] = $fields;
        return $this;
    }

    public function limit($count, $offset = null) {
        if (!is_integer($count))
            throw new InvalidArgumentException('LIMIT necesita un valor entero para la cantidad de filas a devolver (parámetro 1)');

        if (!(is_null($offset) || is_integer($count)))
            throw new InvalidArgumentException('LIMIT necesita un valor entero para el desplazamiento (parámetro 2)');

        $this->qParts['limit'] = array($count, $offset);
        return $this;
    }


    /**
     * Ejecuta la consulta actual y devuelve el resultados. Solo para instrucciones DELETE, UPDATE e INSERT
     * @param null $resultType
     * @return mixed
     * @throws Exception
     */
    public function get($resultType = null) {
        if ($this->type != 'R')
            throw new BadMethodCallException('get() solo se utiliza para instrucciones SELECT');

        try {
            $res = $this->query($this->get_query());

            if (!is_null($resultType) && $res)
                $res->setResultType($resultType);

            return $res;
        } finally {
            $this->clearAll();
        }
    }

    /**
     * Ejecuta la sentencia SQL actual.
     * @param null $resultType
     * @return bool Para instrucciones SELECT devuelve el resultado. Para DELETE, UPDATE e INSERT devuelve un bool
     * @throws Exception
     */
    public function exec($resultType = null) {
        if ($this->type == 'R')
            return $this->get($resultType);

        try {
            return $this->query($this->get_query());
        } finally {
            $this->clearAll();
        }

        return FALSE;
    }

    protected abstract function getSELECT();

    protected abstract function getINSERT();

    protected function getDELETE() {
        if (array_key_exists('from', $this->qParts))
            return 'DELETE FROM ' . $this->qParts['from'] . (array_key_exists('where', $this->qParts) ? ' WHERE ' . $this->qParts['where'] : '');

        throw new Exception('DELETE necesita la cláusula FROM');
    }

    protected function getUPDATE() {
        $q = 'UPDATE ' . $this->qParts['table'];

        if (array_key_exists('set', $this->qParts)) {
            $set = $this->qParts['set'];

            foreach ($set[0] as $i => $field)
                $q .= ($i ? ', ' : ' SET ') . $field . ' = ' . $this->fixValue($set[1][$i]);

            if (array_key_exists('where', $this->qParts))
                return $q . ' WHERE ' . $this->qParts['where'];

            return $q;
        }

        throw new Exception('UPDATE necesita la cláusula SET');
    }

    /**
     * Devuelve la cadena de la consulta actual
     * @return string
     * @throws Exception
     */
    public function get_query() {
        switch ($this->type) {
            case 'C':
                return $this->getINSERT();

            case 'R':
                return $this->getSELECT();

            case 'U':
                return $this->getUPDATE();

            case 'D':
                return $this->getDELETE();

            default:
                return '';
        }
    }
}