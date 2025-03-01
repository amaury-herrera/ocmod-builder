<?php

abstract class DB_result implements Iterator {
    protected $key = 0;
    protected $_current = null;

    protected $results = null;
    protected $resKey = 0;
    protected $curResult;

    protected $resultFn;

    public function __construct($results) {
        $this->results = is_array($results) ? $results : array($results);
        $this->curResult = $this->results[$this->resKey];
        $this->setResultType(DBConfig::$results_type);
    }

    abstract public function free_result();

    abstract public function fetch_object();

    abstract public function fetch_assoc();

    abstract public function fetch_row();

    abstract public function seek($offset);

    abstract public function num_rows();

    public function next() {
        $this->key++;
    }

    public function valid() {
        $this->_current = $this->{$this->resultFn}($this->curResult);
        return (bool)$this->_current;
    }

    public function key() {
        return $this->key;
    }

    public function current() {
        return $this->_current;
    }

    public function rewind() {
        $this->seek(0);
    }

    /**
     * Devuelve la fila actual en el formato especificado (object|assoc|array|row).
     * Devuelve false cuando se alcanzó el final
     * @param null $callback
     * Puede ser un callable que procese el resultado o un array indicando el tipo (valor) del campo (llave) al que debe ser convertido
     * @return bool|object|array
     */
    public function row($callback = null) {
        if ($this->valid()) {
            $this->next();
            $res = $this->_current;

            $call = is_callable($callback, true);
            $array = is_array($callback);
            if ($call)
                call_user_func($callback, $res);
            elseif ($array) {
                if (is_object($res))
                    foreach ($callback as $f => $t) {
                        if (isset($res->$f))
                            settype($res->$f, $t);
                    }
                else
                    foreach ($callback as $f => $t) {
                        if (isset($res[$f]))
                            settype($res[$f], $t);
                    }
            }

            return $res;
        }

        return false;
    }

    /**
     * Devuelve un array con todas las filas en el formato especificado (object|assoc|array|row).
     * @param null $callback
     * Puede ser un callable que procese el resultado o un array indicando el tipo (valor) del campo (llave) al que debe ser convertido
     * @return array
     */
    public function rows($callback = null) {
        $rows = [];
        $call = is_callable($callback, true);
        $array = is_array($callback);
        foreach ($this as $o) {
            if ($call)
                call_user_func($callback, $o);
            elseif ($array) {
                if (is_object($o))
                    foreach ($callback as $f => $t) {
                        if (isset($o->$f))
                            settype($o->$f, $t);
                    }
                else
                    foreach ($callback as $f => $t) {
                        if (isset($o[$f]))
                            settype($o[$f], $t);
                    }
            }

            $rows[] = $o;
        }

        return $rows;
    }

    //Obtiene en un array $limit filas desde la posicion $from, utilizando como llave(s)
    //la(s) columna(s) especificadas en $keyCols (separadas por comas). Si se devuelven dos columnas
    //se asigna el valor directamente a la entrada, de lo contrario se devuelve como array.
    //Si la consulta devuelve más de una fila con el mismo valor en la combinación de llaves
    //se retornará agrupado
    public function rowsByKey($keyCols, $from = 0, $limit = -1, $alwaysArray = false) {
        if ((is_string($keyCols) || is_array($keyCols))) {
            if ($from <= 0 || $this->seek($from)) {
                if (is_string($keyCols))
                    $keyCols = explode(',', $keyCols);
                $keyCols = array_map('trim', $keyCols);
                $keyCount = count($keyCols);

                $this->setResultType('assoc');

                $rs = $ia = [];
                foreach ($this as $r) {
                    if (!$limit--)
                        break;

                    $i = 0;
                    $ref = &$rs;
                    $set = true;
                    $fk = '';
                    foreach ($keyCols as $key) {
                        $k = $r[$key];
                        unset($r[$key]);

                        if ($fk)
                            $fk .= '|';
                        $fk .= $k;

                        $i++;
                        if (!isset($ref[$k])) {
                            $ref[$k] = [];
                            if ($i == $keyCount)
                                $set = false;
                        }

                        $ref = &$ref[$k];
                    }

                    if ($set) {
                        if (!$alwaysArray && isset($ia[$fk])) {
                            $ref = [$ref];
                            unset($ia[$fk]);
                        }
                        $ref[] = count($r) == 1 ? current($r) : $r;
                    } else {
                        $ia[$fk] = 1;
                        if (count($r) == 1)
                            $ref = $alwaysArray ? [current($r)] : current($r);
                        else
                            $ref = $alwaysArray ? [$r] : $r;
                    }
                }

                return $rs;
            }
        }
        return null;
    }

    public function setResultType($type) {
        if (in_array($type = strtolower($type), array('object', 'assoc', 'row')))
            $this->resultFn = 'fetch_' . $type;
        else
            throw new InvalidArgumentException('El tipo de resultado debe ser object, assoc o row');
    }

    public function next_result() {
        if ($this->more_results()) {
            $this->curResult = $this->results[++$this->resKey];
            $this->key = 0;
            return true;
        }

        $this->curResult = null;
        return false;
    }

    public function more_results() {
        return $this->resKey < count($this->results) - 1;
    }
}