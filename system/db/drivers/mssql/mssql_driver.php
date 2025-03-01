<?php

class mssqlDriver extends DB_driver {
    private $mssql;

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

        $host = $this->params->host;
        if (isset($this->params->port) && $this->params->port != '')
            $host .= ',' . $this->params->port;

        $fn = $persistent ? 'mssql_pconnect' : 'mssql_connect';

        if ($this->mssql = $fn($host, $this->params->user, $this->params->pw)) {
            if ($db = @mssql_select_db($this->params->dbname, $this->mssql))
                $this->connected = true;
            else
                $this->close();
        }

        return $this->connected;
    }

    public function close() {
        if ($this->connected) {
            @mssql_close($this->mssql);
            $this->mssql = null;
            $this->connected = false;
        }
    }

    /**
     * Permite escapar consultas y evitar SQL Injections
     * @param $str
     * Valor a escapar
     * @return mixed
     * Devuelve la cadena escapada
     */
    public function escape_str($str) {
        return str_replace("'", "''", $str);
    }

    /**
     * Devuelve el código del último mensaje de error
     * @return mixed
     */
    public function error() {
        if ($q = mssql_query('select @@ERROR as e', $this->mssql))
            if ($r = mssql_fetch_assoc($q))
                return $r['e'];

        //No pudo obtenerse el código de error
        return -1;
    }

    /**
     * Devuelve un texto descriptivo del último mensaje de error
     * @return mixed
     */
    public function error_message() {
        return mssql_get_last_message();
    }

    /**
     * Devuelve el último id generado en una consulta INSERT
     * @return mixed
     */
    public function insert_id() {
        if ($q = mssql_query('select scope_identity() as id', $this->mssql))
            if ($r = mssql_fetch_assoc($q))
                return $r['id'];

        //No pudo obtenerse el código de error
        return -1;

        return $this->mssql->insert_id;
    }

    /**
     * Devuelve el número de filas afectadas por la última consulta UPDATE, DELETE o INSERT
     * @return mixed
     */
    public function affected_rows() {
        return @mssql_rows_affected($this->mssql);
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
     * Hace permanentes los cambios de una transacción
     * @return bool
     */
    function trans_commit() {
        if ($this->_trans_depth > 0)
            return TRUE;

        $this->query('COMMIT TRAN');

        return TRUE;
    }

    /**
     * Deshace las operaciones realizadas durante una transacción
     * @return bool
     */
    function trans_rollback() {
        if ($this->_trans_depth > 0)
            return TRUE;

        $this->query('COMMIT TRAN');

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

        $res = @mssql_query($sql, $this->mssql);

        if (is_bool($res)) {
            if (!$res)
                $this->_trans_status = FALSE;

            return $res;
        }

        return new mysqliResult($this->mssql, $res);
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

        $qRes = mssql_query($sql, $this->mssql);
        if ($this->error() || $qRes === false) {
            $this->_trans_status = FALSE;
            return false;
        }

        $results = array();

        do {
            $res = $this->mssql->store_result();

            if (is_bool($res)) {
                if ($this->error() || $res === false)
                    return false;
            } else
                $results[] = $res;

            if ($this->mssql->more_results())
                $this->mssql->next_result();
            else
                break;
        } while (true);

        return new mysqliResult($this->mssql, $results);
    }

    private $joins = array('N' => ' INNER JOIN ', 'L' => ' LEFT OUTER JOIN ', 'R' => ' RIGHT OUTER JOIN ', 'C' => ' CROSS JOIN ');

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
            $q = 'INSERT INTO ' . $this->qParts['into'][0] . '(' . implode(', ', $this->qParts['into'][1]) . ') VALUES(';

            $i = 0;
            $c = count($this->qParts['values']);
            foreach ($this->qParts['values'] as $v) {
                if (is_array($v)) {
                    $vl = $c == 1 ? '' : '(';
                    $j = 0;
                    foreach ($v as $v2)
                        $vl .= ($j++ ? ', ' : '') . $this->fixValue($v2);

                    $v = $c == 1 ? $vl : "{$vl})";
                } else
                    $v = $this->fixValue($v);

                if ($i++)
                    $q .= ', ';

                $q .= $v;
            }

            return $q . ')';
        }

        throw new Exception('INSERT necesita la cláusula INTO y VALUES');
    }
}