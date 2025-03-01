<?php
require_once('system/languages/' . Config::$language . '/form_validation.php');

define('CHARS_UPPER', "ABCDEFGHIJKLMNÑOPQRSTUVWXYZÁÉÍÓÚÑÜ");
define('CHARS_LOWER', "abcdefghijklmnñopqrstuvwxyzáéíóúñü");
define('CHARS_UPPER_NONSP', "ABCDEFGHIJKLMNOPQRSTUVWXYZ");
define('CHARS_LOWER_NONSP', "abcdefghijklmnopqrstuvwxyz");
define('CHARS_NUMS', "0123456789");

class FormValidation {
    private static $_errors = array();
    private $errors = array();
    private $curField = null;
    private $curLabel = null;
    private $curData = null;
    private $isSet = false;
    private $skip = false;
    private $curRuleCount = 0;
    private $formName;
    private $required = array();
    private $labelsOpenTag = '<strong>';
    private $labelsCloseTag = '</strong>';

    private $postOverride = null;

    public function __construct($formName = 'default', array $rules = null, $overridePostValues = null) {
        $this->formName = $formName ? $formName : 'default';

        $arrays = ['between', 'alpha', 'alpha_numeric'];

        if ($overridePostValues) {
            if (!is_array($overridePostValues))
                throw new Exception('Si especifica un tercer parámetro este debe ser un array llave-valor con los valores a comprobar');

            $this->postOverride = $overridePostValues;
        }

        if (is_array($rules)) {
            foreach ($rules as $field => $fieldRules) {
                if (empty($fieldRules['label']))
                    $this->setRule($field);
                else {
                    $this->setRule($field, trim($fieldRules['label']));
                    unset($fieldRules['label']);
                }

                foreach ($fieldRules as $rule => $value) {
                    if ($mustBeArray = in_array($rule, $arrays)) {
                        if (!is_array($value) || count($value) != 2)
                            throw new Exception($rule . ' debe ser un array de dos valores.');
                    } elseif ($rule == 'in_list' && !is_array($value))
                        throw new Exception('in_list debe ser un array.');

                    if (is_null($value))
                        $this->$rule();
                    else
                        call_user_func_array(array($this, $rule), $mustBeArray ? $value : array($value));
                }
            }
        }
    }

    public function setRule($field, $label = '') {
        if (empty($field = trim($field)))
            throw new BadMethodCallException('Especifique el nombre del campo en setRule');

        if ($this->postOverride)
            $P = &$this->postOverride;
        else
            $P = &$_POST;

        if (preg_match('/^(.*)\[(.*)\]$/i', $field, $m)) {
            if ($this->isSet = (!empty($P[$m[1]]) && is_array($P[$m[1]]) && isset($P[$m[1]][$m[2]])))
                $this->curData = &$P[$m[1]][$m[2]];
        } else
            if ($this->isSet = isset($P[$field]))
                $this->curData = &$P[$field];

        if (!$this->isSet) {
            $v = null;
            $this->curData = &$v;
        }

        $this->curField = $field;
        $this->curLabel = $label;
        $this->curRuleCount = 0;
        $this->skip = $this->isEmptyValue();

        return $this;
    }

    /*
     * Devuelve todos los errores encontrados. $tpl es un array con las llaves: open, item y close. La llave item
     * debe contener los caracteres %s
     */

    private function isEmptyValue() {
        return (
            !$this->isSet ||
            is_null($this->curData) ||
            (is_array($this->curData) && empty($this->curData)) ||
            trim($this->curData) == ''
        );
    }

    /*
     * Devuelve todos los errores encontrados. $tpl es un array con las llaves: open, item y close. La llave item
     * debe contener los caracteres %s
     */

    public static function Error($formField, $return = false) {
        try {
            $res = '';

            if (self::$_errors) {
                if (strpos($formField, '.') === false)
                    $fe = current(self::$_errors);
                else {
                    list($form, $formField) = explode('.', $formField);
                    if (array_key_exists($form, self::$_errors))
                        $fe = self::$_errors[$form];
                    else
                        return;
                }

                if (array_key_exists($formField, $fe))
                    $res = current($fe[$formField]);
            }
        } finally {
            if ($return)
                return $res;

            echo $res;
        }
    }

    public static function hasError($formField) {
        if (self::$_errors) {
            if (strpos($formField, '.') === false)
                return array_key_exists($formField, current(self::$_errors));

            list($form, $formField) = explode('.', $formField);
            if (array_key_exists($form, self::$_errors))
                return array_key_exists($formField, self::$_errors[$form]);
        }

        return false;
    }

    public static function hasErrors($formName = '') {
        return !empty(self::$_errors[$formName ? $formName : key(self::$_errors)]);
    }

    public static function getSummary($formName = '', array $tpl = array()) {
        if (self::$_errors) {
            if (!$formName)
                $formName = key(self::$_errors);

            if (!empty(self::$_errors[$formName])) {
                $f = empty($tpl['item']) ? '<li>%s</li>' : $tpl['item'];

                $ret = empty($tpl['open']) ? '<ul>' : $tpl['open'];
                foreach (self::$_errors[$formName] as $error)
                    $ret .= sprintf($f, current($error));
                $ret .= empty($tpl['close']) ? '</ul>' : $tpl['close'];

                return $ret;
            }
        }

        return '';
    }

    public static function __callStatic($fn, $args) {
        return new self($fn, is_array($args) && $args ? $args[0] : null, is_array($args) && count($args) > 1 ? $args[1] : null);
    }

    /*
     * Chequea que se llame primero a setRule. Devuelve true si se debe procesar la regla
     */

    public function run() {
        if ($this->postOverride)
            $P = &$this->postOverride;
        else
            $P = &$_POST;

        if ($P) {
            self::$_errors[$this->formName] = &$this->errors;
            return empty($this->errors);
        }

        return false;
    }

    public function __call($fn, $args) {
        if ($this->check()) {
            if (preg_match('/.*_callback$/im', $fn)) { //Métodos del controlador actual
                $fn = substr($fn, 0, strlen($fn) - 9);
                if (method_exists(App::$instance, $fn)) {
                    $res = call_user_func_array([App::$instance, $fn], [$this->curData, $this->curField]);
                    if ($res)
                        $this->errors[$this->curField][$fn] = $res;
                }
            } elseif (preg_match('/.*_func$/im', $fn)) { //Función de usuario
                $fn = substr($fn, 0, strlen($fn) - 5);
                if (function_exists($fn)) {
                    $res = call_user_func_array($fn, [$this->curData, $this->curField]);
                    if ($res)
                        $this->errors[$this->curField][$fn] = $res;
                }
            } elseif (!array_key_exists($this->curField, $this->errors) && function_exists($fn)) //Función de PHP para transformar el valor
                $this->curData = call_user_func($fn, $this->curData);
        }

        return $this;
    }

    private function check() {
        if ($this->postOverride)
            $P = &$this->postOverride;
        else
            $P = &$_POST;

        if (!$P)
            return false;

        if (is_null($this->curField))
            throw new BadMethodCallException('Debe llamar primero a setRule');

        $this->curRuleCount++;

        return !$this->skip;
    }

    public function clearError($formField) {
        unset($this->errors[$formField]);
    }

    public function setError($formField, $error) {
        $this->errors[$formField]['user'] = $error;
    }

    public function setLabelsEnclosingTags($open, $close) {
        $this->labelsOpenTag = trim($open);
        $this->labelsCloseTag = trim($close);
    }

    /**
     * Required
     * @return $this
     */
    public function required() {
        if ($this->postOverride)
            $P = &$this->postOverride;
        else
            $P = &$_POST;

        if ($P) {
            $this->check();

            if ($this->curRuleCount > 1)
                throw new BadMethodCallException('Debe especificar "required" como primera regla');

            $this->required[$this->curField] = true;

            if ($this->isEmptyValue())
                $this->errorStr('required');

            $this->skip = false;
        }

        return $this;
    }

    private function errorStr($fn) {
        if (!array_key_exists($this->curField, $this->errors)) { //Un solo error a la vez por campo
            if (is_array($fn))
                $this->errors[$this->curField][$fn[0]] = $fn[1];
            else {
                $args = array_slice(func_get_args(), 1);
                array_unshift(
                    $args,
                    FormValidationErrors::${$fn},
                    $this->labelsOpenTag . ($this->curLabel ? $this->curLabel : $this->curField) . $this->labelsCloseTag
                );

                $this->errors[$this->curField][$fn] = call_user_func_array('sprintf', $args);
            }
        }
    }

    /**
     * If set, then apply rules. Skip rules if unset
     * @return $this
     */
    public function if_set() {
        if ($this->postOverride)
            $P = &$this->postOverride;
        else
            $P = &$_POST;

        if ($P) {
            $this->check();

            if ($this->curRuleCount > 1)
                throw new BadMethodCallException('Debe especificar "if_set" como primera regla');

            $this->skip = $this->isEmptyValue();
        }

        return $this;
    }

    /**
     * Performs a Regular Expression match test.
     * @param $regex
     * @param null $msg
     * Mensaje de error (opcional)
     * @return $this
     */
    public function regex_match($regex, $msg = null) {
        if ($this->check() && !preg_match($regex, $this->curData))
            $this->errorStr($msg ? [__FUNCTION__, $msg] : __FUNCTION__);

        return $this;
    }

    /**
     * Performs a negative Regular Expression match test.
     * @param $regex
     * @param null $msg
     * Mensaje de error (opcional)
     * @return $this
     */
    public function neg_regex_match($regex, $msg = null) {
        if ($this->check() && preg_match($regex, $this->curData))
            $this->errorStr($msg ? [__FUNCTION__, $msg] : __FUNCTION__);

        return $this;
    }

    /**
     * Match one field to another
     * @param $field
     * @return $this
     */
    public function matches($field, $label = null) {
        if ($this->postOverride)
            $P = &$this->postOverride;
        else
            $P = &$_POST;

        if ($this->check() && !(isset($P[$field]) && $this->curData === $P[$field]))
            $this->errorStr(__FUNCTION__, $this->labelsOpenTag . ($label ? $label : $field) . $this->labelsCloseTag);

        return $this;
    }

    /**
     * Detects if the value supplied is equals to the value of the field
     * @param $current_pw
     * @return $this
     */
    public function password_match($current_pw) {
        if ($this->check() && $this->curData !== $current_pw)
            $this->errorStr(__FUNCTION__);

        return $this;
    }

    /**
     * Detects if the value belongs to the list
     * @param $values
     * @return $this
     */
    public function in_list(array $values) {
        if ($this->check() && !in_array($this->curData, $values))
            $this->errorStr(__FUNCTION__);

        return $this;
    }

    /**
     * Detects if the value is in the range
     * @param $a
     * @param $b
     * @return $this
     */
    public function between($a, $b) {
        if ($this->check() && ($a < $b ? $this->curData < $a || $this->curData > $b : $this->curData < $b || $this->curData > $a))
            $this->errorStr(__FUNCTION__, $a, $b);

        return $this;
    }

    /**
     * Match one field to another from de configured database
     * @param $field
     * @return $this
     */
    public function is_unique($field) {
        if ($this->check()) {
            list($table, $field) = explode('.', $field);

            $q = DB::select($field)->from($table)->where($field . ' = ?', $this->curData)->get();
            if ($q->num_rows() > 0)
                $this->errorStr(__FUNCTION__);
        }

        return $this;
    }

    /**
     * Minimum Length
     * @param $val
     * @return $this
     */
    public function min_length($val) {
        if ($this->check() && (preg_match("/[^0-9]/", $val) || (function_exists('mb_strlen') ? mb_strlen($this->curData) < $val : strlen($this->curData) < $val)))
            $this->errorStr(__FUNCTION__, $val);

        return $this;
    }

    /**
     * Max Length
     * @param $val
     * @return $this
     */
    public function max_length($val) {
        if ($this->check() && (preg_match("/[^0-9]/", $val) || (function_exists('mb_strlen') ? mb_strlen($this->curData) > $val : strlen($this->curData) > $val)))
            $this->errorStr(__FUNCTION__, $val);

        return $this;
    }

    /**
     * Exact Length
     * @param $val
     * @return $this
     */
    public function exact_length($val) {
        if ($this->check() && (preg_match("/[^0-9]/", $val) || (function_exists('mb_strlen') ? mb_strlen($this->curData) != $val : strlen($this->curData) != $val)))
            $this->errorStr(__FUNCTION__, $val);

        return $this;
    }

    /**
     * Valid URL
     * @return $this
     */
    public function valid_url() {
        if ($this->check() && !filter_var($this->curData, FILTER_VALIDATE_URL))
            $this->errorStr(__FUNCTION__);

        return $this;
    }

    /**
     * Valid Email
     * @return $this
     */
    public function valid_email() {
        if ($this->check() && !filter_var($this->curData, FILTER_VALIDATE_EMAIL))
            $this->errorStr(__FUNCTION__);

        return $this;
    }

    /**
     * Valid Emails
     * @return $this
     */
    public function valid_emails() {
        if ($this->check()) {
            foreach (explode(',', $this->curData) as $email) {
                if (trim($email) != '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->errorStr(__FUNCTION__);
                    break;
                }
            }
        }

        return $this;
    }

    /**
     * Valid Date
     * @return $this
     */
    public function valid_date() {
        if ($this->check() && preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{1,4})$/', $this->curData)) {
            $s = explode('/', $this->curData);
            $d = (int)$s[0];
            $m = (int)$s[1];
            $y = (int)$s[2];

            if ($d >= 1 && $m >= 1 && $m <= 12 && $y != 0) {
                if ($m == 2) {
                    if ($d <= 28 + ((($y % 4 === 0 && $y % 100 !== 0) || $y % 400 === 0) ? 1 : 0))
                        return $this;
                } else
                    if ($d <= [31, 0, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][$m - 1])
                        return $this;
            }
        }

        $this->errorStr(__FUNCTION__);
        return $this;
    }

    /**
     * Validate IP Address
     * @param $ip
     * @param string $which
     * @return $this
     */
    public function valid_ip() {
        return $this->_valid_ip(__FUNCTION__, $this->curData, '');
    }

    private function _valid_ip($fn, $ip, $which) {
        if ($this->check() && !valid_ip($ip, $which))
            $this->errorStr($fn);

        return $this;
    }

    public function valid_ipv4() {
        return $this->_valid_ip(__FUNCTION__, $this->curData, 'ipv4');
    }

    public function valid_ipv6() {
        return $this->_valid_ip(__FUNCTION__, $this->curData, 'ipv6');
    }

    /**
     * Alpha
     * @return $this
     */
    public function alpha($tildes = false, $spaces = false) {
        if ($this->check() && !preg_match('/^([a-z' . ($tildes ? 'ñÑáéíóúÁÉÍÓÚüÜ' : '') . ($spaces ? ' ' : '') . '])+$/i', $this->curData))
            $this->errorStr(__FUNCTION__);

        return $this;
    }

    /**
     * Alpha-numeric
     * @return $this
     */
    public function alpha_numeric($tildes = false, $spaces = false) {
        if ($this->check() && !preg_match('/^([a-z0-9' . ($tildes ? 'ñÑáéíóúÁÉÍÓÚüÜ' : '') . ($spaces ? ' ' : '') . '])+$/i', $this->curData))
            $this->errorStr(__FUNCTION__);

        return $this;
    }

    /**
     * Alpha-numeric with underscores and dashes
     * @return $this
     */
    public function alpha_dash() {
        if ($this->check() && !preg_match("/^([-a-z0-9_-])+$/i", $this->curData))
            $this->errorStr(__FUNCTION__);

        return $this;
    }

    /**
     * Numeric
     * @return $this
     */
    public function numeric() {
        if ($this->check() && !(bool)preg_match('/^[\-+]?[0-9]*\.?[0-9]+$/', $this->curData))
            $this->errorStr(__FUNCTION__);

        return $this;
    }

    /**
     * Is Numeric
     * @return $this
     */
    public function is_numeric() {
        if ($this->check() && !is_numeric($this->curData))
            $this->errorStr(__FUNCTION__);

        return $this;
    }

    /**
     * Integer
     * @return $this
     */
    public function integer() {
        if ($this->check() && !(bool)preg_match('/^[\-+]?[0-9]+$/', $this->curData))
            $this->errorStr(__FUNCTION__);

        return $this;
    }

    /**
     * Decimal number
     * @return $this
     */
    public function decimal() {
        if ($this->check() && !(bool)preg_match('/^[\-+]?[0-9]+\.[0-9]+$/', $this->curData))
            $this->errorStr(__FUNCTION__);

        return $this;
    }

    /**
     * Greather than
     * @param $min
     * @return $this
     */
    public function greater_than($min) {
        if ($this->check() && (!is_numeric($this->curData) || $this->curData <= $min))
            $this->errorStr(__FUNCTION__, $min);

        return $this;
    }

    /**
     * Less than
     * @param $max
     * @return $this
     */
    public function less_than($max) {
        if ($this->check() && (!is_numeric($this->curData) || $this->curData >= $max))
            $this->errorStr(__FUNCTION__, $max);

        return $this;
    }

    /**
     * Valid Base64
     *
     * Tests a string for characters outside of the Base64 alphabet
     * as defined by RFC 2045 http://www.faqs.org/rfcs/rfc2045
     *
     * @return $this
     */
    public function valid_base64() {
        if ($this->check() && (bool)preg_match('/[^a-zA-Z0-9\/\+=]/', $this->curData))
            $this->errorStr(__FUNCTION__);

        return $this;
    }

    /**
     * Is a Natural number, but not a zero  (1,2,3, etc.)
     * @return $this
     */
    public function is_natural_no_zero() {
        if (!preg_match('/^[0-9]+$/', ($this->curData) || $this->curData == 0))
            $this->errorStr(__FUNCTION__);

        return $this;
    }

    /**
     * Is a Natural number  (0,1,2,3, etc.)
     * @return $this
     */
    public function is_natural() {
        if ($this->check() && !(bool)preg_match('/^[0-9]+$/', $this->curData))
            $this->errorStr(__FUNCTION__);

        return $this;
    }

    /**
     * Is compound only by characters in $chars
     * @param $chars
     * Allowed characters
     * @return $this
     */
    public function only_chars($chars) {
        if ($this->check() && strspn($this->curData, $chars) != strlen($this->curData))
            $this->errorStr(__FUNCTION__);

        return $this;
    }

    /**
     * Not contains any characters in $chars
     * @param $chars
     * Disallowed characters
     * @return $this
     */
    public function not_contains($chars) {
        if ($this->check() && strcspn($this->curData, $chars) < strlen($this->curData))
            $this->errorStr(__FUNCTION__);

        return $this;
    }
}