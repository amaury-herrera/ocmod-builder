<?php

class Ocmod {
    private $sessionName;
    private $sessionData;

    private function prepareSession() {
        $dbConnected = $this->connectDB();

        if (is_string($dbConnected)) {
            echo json_encode(['error' => $dbConnected]);
            die;
        }

        if ($dbConnected === false) {
            echo json_encode(['error' => 'No se ha podido conectar a la base de datos.']);
            die;
        }

        $user_id = 1;

        //Find a user with administrator role
        $qu = DB::query('SELECT user_id FROM @?user WHERE user_group_id = 1', DB_PREFIX);
        if ($qu) {
            if ($qu->num_rows() > 0) {
                $user_id = (int)($qu->row()->user_id);
            } else {
                //Create an administrator user named "ocmod_builder"
                if (DB::query('
                            INSERT INTO @?user(user_group_id, username, password, salt, firstname, lastname, email, ip, image, code, status, date_added)
                                VALUES(1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    DB_PREFIX, 'ocmod_builder', md5('ocmod_builder'), '', 'John', 'Doe', '', '::1', '', '', 1, date('Y/m/d h:i:s'))) {
                    $user_id = (int)DB::insert_id();
                }
            }
        }

        $data = new StdClass();
        $data->user_id = $user_id;
        $data->user_token = $this->getToken();

        $expireDate = '2100/01/01 12:00:00';

        //Prepare a session for this process with a fixed session_id
        $id = md5('ocmod');

        $q = DB::query('SELECT * FROM @?session WHERE session_id = ?', DB_PREFIX, $id);

        if ($q) {
            if ($q->num_rows() === 0) {
                //Session doesn't exist, create a new one
                DB::query('INSERT INTO @?session(session_id, data, expire) VALUES(?, ?, ?)',
                    DB_PREFIX, $id, json_encode($data, JSON_FORCE_OBJECT), $expireDate);

                $sessionData = new StdClass();
                $sessionData->session_id = $id;
                $sessionData->data = $data;
            } else {
                //Session exists, use its data and update de expiration date to avoid lose it
                $sessionData = $q->row();
                $sessionData->data = json_decode($sessionData->data);

                if (!isset($sessionData->data->user_token)) {
                    //Repair the session with our data
                    $sessionData->data = $data;

                    DB::query('UPDATE @?session SET data = ?, expire = ? WHERE session_id = ?',
                        DB_PREFIX, json_encode($data, JSON_FORCE_OBJECT), $expireDate, $id);
                } else {
                    DB::query('UPDATE @?session SET expire = ? WHERE session_id = ?',
                        DB_PREFIX, $expireDate, $id);
                }
            }
        } else {
            echo json_encode(['error' => 'No se ha podido conectar a la base de datos.']);
            die;
        }

        @include_once SOURCE_ROOT_PATH . '/system/config/default.php';

        $this->sessionName = isset($_['session_name']) ? $_['session_name'] : 'OCSESSID';
        $this->sessionData = $sessionData;
    }

    public function createZip() {
        $result = MODEL::OCMOD()->createZip();

        if ($result === false) {
            echo json_encode(['error' => 'No ha sido posible crear el archivo comprimido.']);
            die;
        }

        if (is_string($result)) {
            echo json_encode(['error' => $result]);
            die;
        }

        $zipFilename = App::project()['zipFilename'];
        if (substr($zipFilename, -10) != '.ocmod.zip')
            $zipFilename .= '.ocmod.zip';

        echo json_encode([
            'dlFilename' => basename($result['zipFilename']),
            'filename' => $zipFilename,
            'errors' => $result['errors'],
        ]);
    }

    public function downloadZip() {
        $zipFilename = PATH_TEMP . Post::filename();

        if (file_exists($zipFilename))
            @readfile($zipFilename);
    }

    public function ajax_clearModifications() {
        $this->prepareSession();

        $dirModification = MODEL::Files()->normalizePath(DIR_MODIFICATION);

        //Eliminar el contenido de la carpeta modification. Se mantiene el archivo index.html en la raíz de dicha carpeta
        $result = MODEL::Files()->delTree($dirModification, false,
            function ($path) use ($dirModification) {
                return MODEL::Files()->normalizePath($path) != $dirModification . DS . 'index.html';
            }
        );

        if ($result === false)
            echo json_encode(['error' => 'No se ha podido completar la operación.']);
        else
            echo json_encode(['ok' => true]);

    }

    private function connectDB() {
        include_once('application/config/database.php');
        include_once('system/db/DB.php');
        include_once('system/db/DB_driver.php');
        include_once('system/db/DB_result.php');

        DBConfig::$host = DB_HOSTNAME;
        DBConfig::$name = DB_DATABASE;
        DBConfig::$port = DB_PORT;
        DBConfig::$user = DB_USERNAME;
        DBConfig::$password = DB_PASSWORD;

        if (DB::loadDriver('mysqli')) {
            DB::init();

            return DB::connect(false);
        } else
            return 'El driver de base de datos no es válido';
    }

    private function getToken($length = 32) {
        $string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

        $max = strlen($string) - 1;

        $token = '';
        for ($i = 0; $i < $length; $i++)
            $token .= $string[random_int(0, $max)];

        return $token;
    }

    private function init_curl($url) {
        $curl = curl_init();

        if (substr($url, 0, 5) == 'https')
            curl_setopt($curl, CURLOPT_PORT, 443);

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        //curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_FORBID_REUSE, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_COOKIE, $this->sessionName . '=' . $this->sessionData->session_id . '; path=/');

        return $curl;
    }

    public function ajax_install() {
        $this->prepareSession();

        $url = OPENCART_URL . '/admin/index.php?route=marketplace/installer/upload&user_token=' . $this->sessionData->data->user_token;

        $nextURL = $this->_upload($url);
        while ($nextURL !== true)
            $nextURL = $this->_processURL($nextURL);

        $this->_refreshModifications();

        echo json_encode(['ok' => true]);
    }

    //Upload the ocmod.zip file created ********************************************************
    private function _upload($url) {
        $curl = $this->init_curl($url);

        $zipResult = MODEL::OCMOD()->createZip(App::project()['zipFilename']);

        if ($zipResult === false) {
            echo json_encode(['error' => 'No ha sido posible crear el archivo comprimido.']);
            die;
        }

        if (is_string($zipResult)) {
            echo json_encode(['error' => $zipResult]);
            die;
        }

        $cfile = curl_file_create(APP_ROOT . $zipResult['zipFilename']);
        curl_setopt($curl, CURLOPT_POSTFIELDS, ['file' => $cfile]);

        $json = curl_exec($curl);

        curl_close($curl);

        if ($json === false) {
            echo json_encode(['error' => 'No se ha podido subir el archivo.']);
            die;
        }

        $json = json_decode($json, true);

        if (isset($json['error'])) {
            echo json_encode($json);
            die;
        }

        return $json['next'];
    }

    //Process next URL ********************************************************
    private function _processURL($url) {
        $curl = $this->init_curl($url);
        $json = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        if ($json === false) {
            echo json_encode(['error' => 'No se ha podido completar la operación.']);
            die;
        }

        $json = json_decode($json, true);

        if (isset($json['error'])) {
            echo json_encode($json);
            die;
        }

        return isset($json['next']) ? $json['next'] : true;
    }

    //Process next URL ********************************************************

    private function _refreshModifications() {
        $url = OPENCART_URL . '/admin/index.php?route=marketplace/modification/refresh&user_token=' . $this->sessionData->data->user_token;
        $curl = $this->init_curl($url);
        $json = curl_exec($curl);
        curl_close($curl);

        if ($json === false) {
            echo json_encode(['error' => 'No se ha podido completar la operación.']);
            die;
        }

        $json = json_decode($json, true);

        if (isset($json['error'])) {
            echo json_encode($json);
            die;
        }
    }
}