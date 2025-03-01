<?php

class mysqliDriver extends DB_driver {
    private $mysqli = null;
    private $joins = array('N' => ' INNER JOIN ', 'L' => ' LEFT OUTER JOIN ', 'R' => ' RIGHT OUTER JOIN ', 'C' => ' CROSS JOIN ');

    /**
     * Permite conectarse a la base de datos
     * @param $persistent
     * Define si la conexión debe ser persistente o no
     * @return bool
     * True si se conectó con éxito al servidor.
     * False en caso contrario
     */
    public function connect($persistent) {
        $this->close();

        $this->mysqli = mysqli_init();
        $this->mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
        $this->mysqli->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, TRUE);

        $hostname = ($persistent === TRUE && version_compare(PHP_VERSION, '5.3.0') >= 0) ? 'p:' . $this->params->host : $this->params->host;

        $ret = @$this->mysqli->real_connect($hostname, $this->params->user, $this->params->pw, $this->params->dbname, $this->params->port) ? (bool)$this->mysqli : FALSE;

        if ($ret && $this->params->charset)
            $this->mysqli->set_charset($this->params->charset);

        $this->connected = $ret;

        return $ret;
    }

    /**
     * Cierra la conexión actual
     * @return bool
     */
    public function close() {
        if ($this->mysqli) {
            $this->mysqli->close();
            $this->mysqli = null;
            $this->connected = false;
        }
    }

    /**
     * Permite escapar valores utilizados en consultas y evitar SQL Injections
     * @param $str
     * Valor a escapar
     * @return mixed
     * Devuelve la cadena escapada
     */
    public function escape_str($str) {
        return $this->mysqli ? $this->mysqli->real_escape_string($str) : $str;
    }

    /**
     * Devuelve un texto descriptivo del último mensaje de error
     * @return mixed
     */
    public function error_message() {
        return $this->mysqli->error;
    }

    /**
     * Devuelve el último id generado en una consulta INSERT
     * @return mixed
     */
    public function insert_id() {
        return $this->mysqli->insert_id;
    }

    /**
     * Devuelve el número de filas afectadas por la última consulta UPDATE, DELETE o INSERT
     * @return mixed
     */
    public function affected_rows() {
        return $this->mysqli->affected_rows;
    }

    /**
     * Comienza una transacción
     * @return bool
     */
    function trans_begin() {
        if ($this->_trans_depth > 0)
            return TRUE;

        $this->query('SET AUTOCOMMIT=0');
        $this->query('START TRANSACTION'); // can also be BEGIN or BEGIN WORK

        return TRUE;
    }

    /**
     * Ejecuta una sola consulta
     * @param $sql
     * Consulta a ejecutar
     * @param null $bindings
     * Opcional. Valores con los cuales se sustituirá el caracter ?
     * @return mixed
     * DB_Result para las consultas que devuelven un conjunto de datos. True/False para otras consultas
     */
    public function query($sql/*[, $bindings = null]*/) {
        if (!is_null($bindings = $this->getBindings(func_get_args(), 1)))
            $sql = $this->bind($sql, $bindings);

        $this->_lastQuery = $sql;

        $res = $this->mysqli->query($sql);

        if (is_bool($res)) {
            if (!$res)
                $this->_trans_status = FALSE;

            return $res;
        }

        return new mysqliResult($this->mysqli, $res);
    }

    /**
     * Hace permanentes los cambios de una transacción
     * @return bool
     */
    function trans_commit() {
        if ($this->_trans_depth > 0)
            return TRUE;

        $this->query('COMMIT');
        $this->query('SET AUTOCOMMIT=1');

        return TRUE;
    }

    /**
     * Deshace las operaciones realizadas durante una transacción
     * @return bool
     */
    function trans_rollback() {
        if ($this->_trans_depth > 0)
            return TRUE;

        $this->query('ROLLBACK');
        $this->query('SET AUTOCOMMIT=1');

        return TRUE;
    }

    /**
     * Ejecuta una o varias consultas
     * @param $sql
     * Consulta(s) a ejecutar
     * @param null $bindings
     * Opcional. Valores con los cuales se sustituirá el caracter ?
     * @return mixed
     * El resultado se obtiene solo para la primera consulta.
     * Devuelve DB_Result para las consultas que devuelven un conjunto de datos. True/False para otras consultas
     */
    public function multi_query($sql/*[, $bindings = null]*/) {
        if (!is_null($bindings = $this->getBindings(func_get_args(), 1)))
            $sql = $this->bind($sql, $bindings);

        $this->_lastQuery = $sql;

        $qRes = $this->mysqli->multi_query($sql);
        if ($this->error() || $qRes === false) {
            $this->_trans_status = FALSE;
            return false;
        }

        $results = array();

        do {
            $res = $this->mysqli->store_result();

            if (is_bool($res)) {
                if ($this->error() || $res === false)
                    return false;
            } else
                $results[] = $res;

            if ($this->mysqli->more_results())
                $this->mysqli->next_result();
            else
                break;
        } while (true);

        return new mysqliResult($this->mysqli, $results);
    }

    /**
     * Devuelve el código del último mensaje de error
     * @return mixed
     */
    public function error() {
        return $this->mysqli->errno;
    }

    protected function getSELECT() {
        $query = "SELECT {$this->qParts['fields']}";

        if (array_key_exists('from', $this->qParts)) {
            $query .= "\rFROM {$this->qParts['from']}";

            if (array_key_exists('joins', $this->qParts)) {
                foreach ($this->qParts['joins'] as $join)
                    $query .= "{$this->joins[$join[2]]}\r     " . trim($join[0]) . " ON {$join[1]}";
            }

            foreach (array('where', 'group by', 'having', 'order by') as $v)
                if (array_key_exists($v, $this->qParts))
                    $query .= "\r" . strtoupper($v) . ' ' . $this->qParts[$v];

            if (array_key_exists('limit', $this->qParts))
                $query .= "\rLIMIT " . $this->qParts['limit'][0] . (is_null($this->qParts['limit'][1]) ? '' : ', ' . $this->qParts['limit'][1]);
        }

        return $query;
    }

    protected function getINSERT() {
        if (array_key_exists('into', $this->qParts) && array_key_exists('values', $this->qParts)) {
            $q = 'INSERT INTO ' . $this->qParts['into'][0] . '(' . implode(', ', $this->qParts['into'][1]) . ') VALUES';

            $i = 0;
            foreach ($this->qParts['values'] as $v) {
                if (is_array($v)) {
                    $vl = '(';
                    $j = 0;
                    foreach ($v as $v2)
                        $vl .= ($j++ ? ', ' : '') . $this->fixValue($v2);

                    $v = $vl . ')';
                } else
                    $v = '(' . $this->fixValue($v) . ')';

                if ($i++)
                    $q .= ', ';

                $q .= $v;
            }

            return $q;
        }

        throw new Exception('INSERT necesita la cláusula INTO y VALUES');
    }
}