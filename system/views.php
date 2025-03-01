<?php
if (!class_exists('Config'))
    die('');

class Views {
    private static $currentLayout = null;
    private static $currentBlock = null;
    private static $blocks = [];
    private static $isRendering = false;
    private static $someRendered = false;
    private static $renderTimes = 0;
    private static $defines = [];
    private static $data = [];

    /*
     * Permite hacer llamadas a un bloque e insertarlo 'directamente a través de su nombre, es decir:
     *    Views::headContent();  //Inserta el contenido del bloque headContent
     *
     * que sería equivalente a:
     *    Views::Block('headContent');
     */
    public static function __callStatic($key, $args) {
        call_user_func_array('self::Block', array_merge([$key], $args));
    }

    /**
     * Define una llave que puede utilizarse como bandera en otras vistas o en el layout
     * @param $key
     */
    public static function define($key) {
        self::$defines[$key] = true;
    }

    /**
     * Indica si está o no definida la llave especificada
     * @param $key
     * @return bool
     */
    public static function defined($key) {
        return array_key_exists($key, self::$defines);
    }

    /**
     * Agrega valores a una llave. Si solo se especifica la llave, se devolverá el valor de la misma.
     * @param $key
     * Nombre de la llave
     * @param $value
     * Valor a agregar a la llave
     * @param bool $allowDuplicates
     * Indica si se admiten valores duplicados o no. Por defecto no se admiten duplicados.
     * @return void
     */
    public static function data($key, $value = null, $allowDuplicates = false) {
        if (func_num_args() === 1)
            return array_key_exists($key, self::$data) ? self::$data[$key] : null;

        if (!empty($value))
            if (array_key_exists($key, self::$data)) {
                if (!$allowDuplicates && !in_array($value, self::$data))
                    self::$data[$key][] = $value;
            } else
                self::$data[$key] = [$value];
    }

    /**
     * Agrega valores a una llave a partir de un array
     * @param $key
     * Nombre de la llave
     * @param $values
     * Valores a agregar a la llave
     * @param bool $allowDuplicates
     * Indica si se admiten valores duplicados o no. Por defecto no se admiten duplicados.
     * @return void
     */
    public static function dataArray($key, array $values, $allowDuplicates = false) {
        foreach (array_unique($values) as $value)
            self::data($key, $value, $allowDuplicates);
    }

    /**
     * Indica si un bloque existe o no
     * @param $blockName
     * @return bool
     */
    public static function hasBlock($blockName) {
        return array_key_exists(strtolower($blockName), self::$blocks);
    }

    /**
     * Inicia un bloque de contenido que debe ser sustituido en un bloque del layout. Se utiliza en las vistas
     * @param $blockName
     * Nombre del bloque
     */
    public static function BeginBlock($blockName, $clean = true) {
        if (!($blockName = strtolower(trim($blockName))))
            throw new BadMethodCallException('Debe especificar el nombre de un bloque de contenido');

        self::$someRendered = true;

        if (self::$currentBlock) {
            if (array_key_exists(self::$currentBlock, self::$blocks))
                self::$blocks[self::$currentBlock] .= ob_get_clean();
            else
                self::$blocks[self::$currentBlock] = ob_get_clean();
        } else
            if ($clean)
                ob_clean(); //Para no mandar al navegador lo generado entre EndBlock y BeginBlock

        self::$currentBlock = $blockName;
        ob_start();
    }

    /**
     * Devuelve el contenido asignado a un bloque
     * @param $blockName
     * Nombre del bloque
     * @return mixed|string
     */
    public static function GetBlockContent($blockName) {
        $blockName = strtolower(trim($blockName));

        return empty(self::$blocks[$blockName]) ? '' : self::$blocks[$blockName];
    }

    /**
     * Asigna contenido a un bloque que debe ser sustituido en un bloque del layout
     * @param $blockName
     * Nombre del bloque
     * @param $content
     * Contenido del bloque
     */
    public static function SetBlockContent($blockName, $content) {
        if (($blockName = strtolower(trim($blockName))) && $content) {
            self::$blocks[$blockName] = is_array($content) ? implode('', $content) : (string)$content;
            self::$someRendered = true;
        }
    }

    /**
     * Asigna el contenido de un archivo a un bloque, este contenido será sustituido en un bloque del layout
     * @param $blockName
     * Nombre del bloque
     * @param $file
     * Ruta y nombre del archivo
     */
    public static function SetBlockContentFromFile($blockName, $file) {
        if (($blockName = strtolower(trim($blockName))) && file_exists($file)) {
            self::$blocks[$blockName] = array($file);
            self::$someRendered = true;
        }
    }

    /**
     * Añade contenido a un bloque que debe ser sustituido en un bloque del layout
     * @param $blockName
     * Nombre del bloque
     * @param $content
     * Contenido del bloque
     */
    public static function AppendBlockContent($blockName, $content) {
        if (($blockName = strtolower(trim($blockName))) && $content) {
            self::$someRendered = true;

            if (array_key_exists($blockName, self::$blocks)) {
                if (is_array(self::$blocks[$blockName])) {
                    ob_start();
                    include(self::$blocks[$blockName][0]);
                    self::$blocks[$blockName] .= ob_get_clean();
                } else
                    self::$blocks[$blockName] .= is_array($content) ? implode('', $content) : (string)$content;
            } else
                self::$blocks[$blockName] = is_array($content) ? implode('', $content) : (string)$content;
        }
    }

    /**
     * Añade el contenido de un archivo a un bloque, este contenido será sustituido en un bloque del layout
     * @param $blockName
     * Nombre del bloque
     * @param $file
     * Ruta y nombre del archivo
     */
    public static function AppendBlockContentFromFile($blockName, $file) {
        if (($blockName = strtolower(trim($blockName))) && file_exists($file)) {
            self::$someRendered = true;

            if (array_key_exists($blockName, self::$blocks)) {
                ob_start();

                if (is_array(self::$blocks[$blockName]))
                    include(self::$blocks[$blockName][0]);

                include($file);
                self::$blocks[$blockName] .= ob_get_clean();
            } else
                self::$blocks[$blockName] = array($file);
        }
    }

    /**
     * Genera el contenido de la vista y la incluye en el layout si lo tuviera.
     * Se puede llamar a una vista dentro de otra, siempre y cuando no se haya llamado a UseLayout u otra función de Views
     * @param $viewName
     * Nombre (o ruta relativa a application/views) de la vista, sin incluir la extensión. Si comienza con ~ se buscará en la ruta especificada
     * en lugar de la carpeta configurada en Config::$viewsRoot
     * @param null $params
     * Parámetros a pasar a la vista que se generará
     * @throws Exception
     */
    public static function Render($viewName, $params = null) {
        if (self::$currentLayout)
            throw new BadMethodCallException('Se ha llamado a Render más de una vez');

        $rt = ++self::$renderTimes;

        self::$isRendering = true;
        self::$currentLayout = null;
        self::_render($viewName, $params);
        self::EndBlock();

        if (!self::$currentLayout && !Config::$skipDefaultLayout)
            self::UseLayout(Config::$defaultLayout);

        if (self::$currentLayout) {
            if ($rt < self::$renderTimes) {
                require(self::$currentLayout);
            } else {
                if ($rt == 1 || !self::$someRendered)
                    require(self::$currentLayout);
            }
        }

        if ($rt == 1)
            self::$isRendering = self::$someRendered = self::$currentLayout = false;

        self::$renderTimes--;
    }

    /**
     * Permite que los datos pasados a la vista solo sean visibles en ella y no en el layout
     * @param $viewName
     * Nombre o ruta de la vista
     * @param $params
     * Parámetros a pasar a la vista
     */
    private static function _render($viewName, $params) {
        if (is_array($params))
            foreach ($params as $key => $value)
                $$key = $value;

        ob_start();
        require(APP_DIR . trim(Config::$viewsRoot, '/') . "/{$viewName}.php");
    }

    /**
     * Termina el bloque de contenido previamente abierto con BeginBlock. Se utiliza en las vistas
     */
    public static function EndBlock() {
        if (self::$currentBlock) {
            if (array_key_exists(self::$currentBlock, self::$blocks))
                self::$blocks[self::$currentBlock] .= ob_get_clean();
            else
                self::$blocks[self::$currentBlock] = ob_get_clean();

            self::$currentBlock = null;

            ob_start(); //Para no mandar al navegador lo generado entre EndBlock y BeginBlock
        }
    }

    /**
     * Especifica el layout que debe utilizarse para generar el contenido. Se utiliza en las vistas
     * @param null $layoutName
     * Nombre (o ruta relativa a la carpeta application/vies/layouts) de la plantilla (sin extensión)
     * @throws Exception
     */
    public static function UseLayout($layoutName = null) {
        if (self::$currentLayout)
            throw new BadMethodCallException('Se ha llamado a Render más de una vez');

        if (!self::$isRendering)
            throw new BadMethodCallException('UseLayout solo debe llamarse dentro de una vista');

        self::$someRendered = true;

        if (empty($layoutName))
            $layoutName = Config::$defaultLayout;

        if (!file_exists($fName = APP_DIR . trim('views/layouts/' . trim(Config::$layoutsFolder, '/'), '/') . "/{$layoutName}.php"))
            throw new Exception('No existe el archivo de plantilla ' . $fName);

        self::$currentLayout = $fName;
    }

    /**
     * Define un bloque de contenido dentro de un layout. Este bloque se sustituirá por el contenido generado
     * en la vista o con SetBlockContent y SetBlockContentFromFile
     * @param $blockName
     * Nombre del bloque a insertar
     * @param $before
     * Si existe el bloque, insertar este valor antes
     * @param $after
     * Si existe el bloque, insertar este valor después
     */
    public static function Block($blockName, $before = '', $after = '') {
        if (array_key_exists($blockName = strtolower(trim($blockName)), self::$blocks)) {
            $bc = self::$blocks[$blockName];
            echo $before;
            if (is_array($bc))
                include($bc[0]);
            else
                echo $bc;
            echo $after;
        }
    }

    /**
     * Inserta contenido desde un partial.
     * @param $partialName
     * nombre del archivo, sin la extensión (o ruta relativa a la raíz, comenzando con !)
     * @param array|null $params
     * Parámetros a pasar al Partial
     * @param bool $return
     * Especificar true si se desea obtener el contenido del partial
     * @return void|string
     */
    public static function Partial($partialName, array $params = null, $return = false) {
        if (is_array($params))
            foreach ($params as $key => $value)
                $$key = $value;

        if ($return)
            ob_start();

        if ($partialName[0] == '!')
            require(ltrim(substr($partialName, 1), '/') . '.php');
        else
            require(APP_DIR . 'views/partials/' . trim(trim(Config::$partialsFolder, '/'), '/') . "/{$partialName}.php");

        if ($return)
            return ob_get_clean();
    }
}