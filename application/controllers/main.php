<?php

//print_r( $_SERVER);
class Main {
    public function index() {
        MODEL::Cache()->recreateIfNeeded();
        Views::Render('main');
    }

    /**
     * Recrea la caché
     * @return void
     */
    public function ajax_recreateCache() {
        //MODEL::Cache()->recreate();
        MODEL::Cache()->setUpdateConfig(true);
        echo '{ok: true}';
    }

    /**
     * Actualiza la última ruta utilizada para el proyecto activo
     * @return void
     */
    public function ajax_saveLastPath() {
        $cfg = App::Config();
        $curProj = App::currentProject();

        $projects = $cfg->projects;
        $projects[$curProj]['lastPath'] = Post::path();
        $projects[$curProj]['lastPathOpened'] = (int)Post::opened();

        $cfg->projects = $projects;
        $cfg->update();
    }

    /**
     * Actualiza la lista de archivos abiertos en el proyecto activo
     * @return void
     */
    public function ajax_saveOpenedFiles() {
        $cfg = App::Config();
        $curProj = App::currentProject();

        $files = Post::openedFiles();
        if (empty($files))
            $files = [];

        $projects = $cfg->projects;
        $projects[$curProj]['openedFiles'] = $files;
        $projects[$curProj]['lastOpenedFile'] = (int)Post::lastOpenedFile();

        $cfg->projects = $projects;

        echo json_encode(['ok' => $cfg->update()]);
    }

    /**
     * Actualiza la última ruta utilizada para el proyecto activo
     * @return void
     */
    public function ajax_saveEditorOptions() {
        $cfg = App::Config();
        $cfg->theme = Post::theme();
        $cfg->fontSize = (int)Post::fontSize();
        $cfg->softWraps = (int)Post::softWraps();
        $cfg->showLineNumbers = (int)Post::showLineNumbers();
        $cfg->softTabs = (int)Post::softTabs();
        $cfg->tabSize = (int)Post::tabSize();

        echo json_encode(['ok' => $cfg->update()]);
    }

    /**
     * Guarda el estado (abierto/cerrado) de la última ruta utilizada en el proyecto activo
     * @return void
     */
    public function ajax_saveLastOpened() {
        $cfg = App::Config();
        $projects = $cfg->projects;
        $projects[App::currentProject()]['lastPathOpened'] = (int)Post::opened();
        $cfg->projects = $projects;
        $cfg->update();

        echo 1;
    }

    /**
     * Obtiene los archivos de la carpeta especificada
     * @return void
     */
    public function ajax_get_files() {
        $this->ajax_saveLastPath();

        echo json_encode(MODEL::Files()->getFileList(Post::path()));
    }

    /**
     * Cambia el proyecto activo
     * @return void
     */
    public function ajax_openProject() {
        $cfg = App::Config();
        $projects = $cfg->projects;

        if (array_key_exists($code = Post::projectCode(), $projects)) {
            $cfg->currentProject = $code;

            echo json_encode(['ok' => $cfg->update()]);
        } else
            echo json_encode(['error' => 'El proyecto solicitado no existe.']);
    }

    /**
     * Crea un nuevo proyecto y lo establece como activo si es el primero
     * @return void
     */
    public function ajax_createProject() {
        $cfg = App::Config();

        $code = strtolower(Post::code());

        $projects = $cfg->projects;
        if (!$projects) {
            $projects = [];
            $cfg->currentProject = $code;
        }

        if (array_key_exists($code, $projects)) {
            echo json_encode(['error' => 'Ya existe un proyecto con el código: ' . Post::code() . '.']);
            return;
        }

        $projects[$code] = [
            'projectName' => Post::projectName(),
            'root_path' => Post::root_path(),
            'url' => Post::url(),
            'zipFilename' => Post::zipFilename(),
            'name' => Post::name(),
            'version' => Post::version(),
            'author' => Post::author(),
            'link' => Post::link(),
            'updateCache' => true,
            'lastPath' => '/admin',
            'lastPathOpened' => 0,
            'openedFiles' => [],
            'lastOpenedFile' => -1
        ];

        $cfg->projects = $projects;

        echo json_encode(['ok' => $cfg->update()]);
    }

    /**
     * Crea un nuevo proyecto y lo establece como activo si es el primero
     * @return void
     */
    public function ajax_updateProject() {
        if (!$this->checkRoot(Post::root_path())) {
            echo json_encode(['error' => 'La carpeta raíz de OpenCart especificada no es válida.']);
            return;
        }

        if (!$this->checkURL(Post::url())) {
            echo json_encode(['error' => 'La URL de OpenCart especificada no es válida.']);
            return;
        }

        $code = strtolower(Post::code());

        $cfg = App::Config();

        $projects = $cfg->projects;

        //Verificar que existe el proyecto a actualizar
        $curProject = App::currentProject();
        if (!array_key_exists($curProject, $projects)) {
            echo json_encode(['error' => 'El proyecto no existe.']);
            return;
        }

        $dirRenamed = false;

        //Si cambia el código, actualizar la llave en projects y renombrar la carpeta del proyecto
        if ($code !== $curProject) {
            if (array_key_exists($code, $projects)) {
                echo json_encode(['error' => 'Ya existe un proyecto con el código: ' . Post::code() . '.']);
                return;
            }

            $dirRenamed = @rename('projects' . DS . $curProject, 'projects' . DS . $code);
            if (!$dirRenamed) {
                echo json_encode(['error' => 'No ha sido posible actualizar el nombre de la carpeta de trabajo del proyecto.']);
                return;
            }

            $cfg->currentProject = $code;

            $projects[$code] = $projects[$curProject];
            unset($projects[$curProject]);
        }

        $project = &$projects[$code];

        //Si cambia la ruta, actualizamos la cache
        if ($project['root_path'] !== Post::root_path()) {
            $project['updateCache'] = true;
        }

        $project['projectName'] = Post::projectName();
        $project['root_path'] = Post::root_path();
        $project['url'] = Post::url();
        $project['zipFilename'] = Post::zipFilename();
        $project['name'] = Post::name();
        $project['version'] = Post::version();
        $project['author'] = Post::author();
        $project['link'] = Post::link();
        $project['lastPath'] = '/admin';
        $project['lastPathOpened'] = 0;

        $cfg->projects = $projects;

        $configUpdated = $cfg->update();

        //Si no se puede actualizar, volver a poner el directorio del proyecto como estaba
        if (!$configUpdated && $dirRenamed) {
            @rename('projects' . DS . $code, 'projects' . DS . $curProject);
        }

        echo json_encode(['ok' => $configUpdated]);
    }

    private function checkRoot($path): bool {
        $path = rtrim($path, '\\/');

        return is_dir($path) && is_dir($path . '/admin') && is_dir($path . '/catalog') && is_readable($path);
    }

    private function checkURL($url): bool {
        $curl = curl_init();

        $url = trim($url, '/') . '/admin';

        if (substr($url, 0, 5) == 'https')
            curl_setopt($curl, CURLOPT_PORT, 443);

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_FORBID_REUSE, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_exec($curl);

        $cinfo = curl_getinfo($curl);

        return floor($cinfo['http_code'] / 100) === 2 || $cinfo['http_code'] === 301;
    }

    public function ajax_checkRoot() {
        echo json_encode(['ok' => $this->checkRoot(Post::path())]);
    }

    public function ajax_checkURL() {
        echo json_encode(['ok' => $this->checkURL(Post::url())]);
    }

    public function ajax_get_file() {
        $file = trim(str_replace(['\\', '/'], DS, Post::file()), '\\/');

        $srcFilename = Post::action() == 'upload'
            ? PATH_UPLOAD . $file
            : SOURCE_ROOT_PATH . $file;

        switch (Post::action()) {
            case 'orig': //Archivo original
                $srcFilename = SOURCE_ROOT_PATH . $file;
                if (file_exists($srcFilename))
                    echo json_encode(['content' => file_get_contents($srcFilename), 'ts' => filemtime($srcFilename), 'isDiff' => false]);
                else
                    echo json_encode(['error' => 'El archivo no existe.']);
                return;

            case 'upload': //Archivo en la carpeta upload
                $srcFilename = PATH_UPLOAD . $file;
                if (file_exists($srcFilename))
                    echo json_encode(['content' => file_get_contents($srcFilename), 'ts' => filemtime($srcFilename), 'isDiff' => false]);
                else
                    echo json_encode(['error' => 'El archivo no existe.']);
                return;

            case 'ocmod': //Archivo con solicitud de cambios. Si no existe, se envía el original, todavía sin cambios
                $modFilename = 'projects/' . App::currentProject() . DS . 'ocmod' . DS . $file;
                if (!file_exists($modFilename)) {
                    $modFilename = SOURCE_ROOT_PATH . $file;
                    if (!file_exists($modFilename)) {
                        echo json_encode(['error' => 'El archivo no existe.']);
                        return;
                    }
                }

                echo json_encode(['content' => file_get_contents($modFilename), 'ts' => filemtime($modFilename), 'isDiff' => false]);
                return;

            case 'diff':
                $srcFilename = SOURCE_ROOT_PATH . $file;
                $modFilename = trim(DIR_STORAGE, '/\\') . DS . 'modification' . DS . $file;
                if (!file_exists($modFilename))
                    $modFilename = $srcFilename;

                [$isDiff, $lines] = MODEL::Diff()->calculateDiff($srcFilename, $modFilename);

                echo json_encode(['content' => $lines, 'ts' => filemtime($modFilename), 'isDiff' => $isDiff]);
                return;

            default:
                echo json_encode(['error' => 'Acción no válida.']);
        }
    }

    public function ajax_saveFile() {
        $content = Post::content();

        switch ($action = Post::action()) {
            case 'upload':
                $targetRoot = PATH_UPLOAD;
                break;
            case 'ocmod':
                $targetRoot = PATH_OCMOD;
                break;
            default:
                echo json_encode(['error' => 'Los parámetros no válidos.']);
                return;
        }

        $fullPath = $targetRoot . MODEL::Files()->normalizePath(trim(Post::path(), '\\/'));
        $filePath = $fullPath . DS . Post::filename();

        $fileExisted = file_exists($filePath);

        if ($action == 'ocmod') {
            //Si el archivo existe y el contenido no contiene bloques OCMOD, se puede eliminar
            if (!MODEL::OCMOD()->processContent($content, $fullPath)) {
                if ($fileExisted) {
                    @unlink($filePath);

                    //Disminuir en 1 la cantidad de archivos ocmod en las ramas padres
                    if (!MODEL::Cache()->update(Post::path(),
                        function (&$leaves) {
                            foreach ($leaves as &$leaf) {
                                if (isset($leaf['o'])) {
                                    $leaf['o']--;
                                    if ($leaf['o'] <= 0)
                                        unset($leaf['o']);
                                }
                            }
                        })) {
                        MODEL::Cache()->setUpdateConfig(true);
                    }
                }

                echo json_encode(['noChanges' => true, 'deleted' => $fileExisted]);
                return;
            }

            //Si tiene errores no permitir guardarlo
            if ($errors = MODEL::OCMOD()->errors) {
                echo json_encode(['error' => $errors[0]]);
                return;
            }
        }

        if (!is_dir($fullPath)) {
            @mkdir($fullPath, 0777, true);
            if (!is_dir($fullPath)) {
                echo json_encode(['error' => 'El directorio de destino no existe y no se pudo crear.']);
                return;
            }
        }

        if (file_put_contents($filePath, $content) === false) {
            echo json_encode(['error' => 'No ha sido posible guardar el archivo.']);
            return;
        }

        //Al llegar aquí, el archivo se guardó con éxito

        if ($action == 'ocmod' && !$fileExisted) {
            //El archivo se acaba de crear, aumentar en 1 la cantidad de archivos ocmod en las ramas padres
            if (!MODEL::Cache()->update(Post::path(),
                function (&$leaves) {
                    foreach ($leaves as &$leaf) {
                        if (isset($leaf['o']))
                            $leaf['o']++;
                        else
                            $leaf['o'] = 1;
                    }
                })) {
                MODEL::Cache()->setUpdateConfig(true);
            }
        }

        echo json_encode(['ok' => true, 'justCreated' => !$fileExisted]);
    }

    private function splitStringByLines(string $input): array {
        return preg_split('/(.*\R)/', $input, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    }

    public function ajax_createDir() {
        $result = MODEL::Files()->newDir(Post::path(), Post::name());

        if ($result === true) {
            if (!MODEL::Cache()->update(Post::path(),
                function (&$leaves) {
                    //Aumentar en 1 a la cantidad de carpetas nuevas en las ramas padres
                    foreach ($leaves as &$leaf) {
                        if (isset($leaf['f']))
                            $leaf['f']++;
                        else
                            $leaf['f'] = 1;
                    }

                    $o = new StdClass;
                    $o->n = Post::name();
                    $o->new = true;
                    $o->c = [];

                    $leaves[count($leaves) - 1]['c'][] = $o;
                })) {
                MODEL::Cache()->setUpdateConfig(true);
            }
        }

        echo json_encode(['result' => $result]);
    }

    public function ajax_renameDir() {
        $result = MODEL::Files()->renDir(Post::path(), Post::name());

        if ($result === true) {
            //Actualizar el nombre de la carpeta en la caché
            if (!MODEL::Cache()->update(Post::path(),
                function (&$leaves) {
                    $leaves[count($leaves) - 1]['n'] = Post::name();
                })) {
                MODEL::Cache()->setUpdateConfig(true);
            }
        }

        echo json_encode(['result' => $result]);
    }

    public function ajax_removeDir() {
        $result = MODEL::Files()->delTree(PATH_UPLOAD . Post::path(), true);

        if ($result === true) {
            if (!MODEL::Cache()->update(Post::path(),
                function (&$leaves) {
                    $leaveToDelete = $leaves[count($leaves) - 1];

                    $props = ['u', 'o', 'f'];
                    foreach ($props as $prop)
                        $$prop = empty($leaveToDelete[$prop]) ? 0 : $leaveToDelete[$prop];

                    $f++; //La propia carpeta a eliminar no se cuenta en su propiedad "f" (NO QUITAR)

                    $parent = &$leaves[count($leaves) - 2];
                    $index = array_search($leaveToDelete, $parent['c']);
                    unset($parent['c'][$index]);

                    foreach ($leaves as &$leaf) {
                        foreach ($props as $prop) {
                            if (isset($leaf[$prop])) {
                                if ($leaf[$prop] > $$prop)
                                    $leaf[$prop] -= $$prop;
                                else
                                    unset($leaf[$prop]);
                            }
                        }
                    }
                })) {
                MODEL::Cache()->setUpdateConfig(true);
            }
        } else
            MODEL::Cache()->setUpdateConfig(true);

        echo json_encode(['result' => $result]);
    }

    public function ajax_renameFile() {
        echo json_encode(['result' => MODEL::Files()->renameFile(Post::path(), Post::name())]);
    }

    public function ajax_deleteFile() {
        $result = MODEL::Files()->deleteFile(Post::path(), Post::name());

        if ($result === true) {
            if (!MODEL::Cache()->update(Post::path(),
                function (&$leaves) {
                    $u = Post::u();
                    $o = Post::o();

                    foreach ($leaves as &$leaf) {
                        if ($u) {
                            if (isset($leaf['u'])) {
                                $leaf['u']--;
                                if ($leaf['u'] <= 0)
                                    unset($leaf['u']);
                            }
                        } else
                            if ($o && isset($leaf['o'])) {
                                $leaf['o']--;
                                if ($leaf['o'] <= 0)
                                    unset($leaf['o']);
                            }
                    }
                })) {
                MODEL::Cache()->setUpdateConfig(true);
            }
        }

        echo json_encode(['result' => $result]);
    }
}