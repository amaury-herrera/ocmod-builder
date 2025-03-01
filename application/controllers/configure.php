<?php

class Configure {
    public function index() {
        $data = null;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            Post::root_path(MODEL::Files()->normalizePath(rtrim(Post::root_path(), '\\/')));

            $f = FormValidation::form()
                ->setRule('projectName', 'Nombre del proyecto')->required()->trim()->alpha(true, true)->max_length(64)
                ->setRule('root_path', 'Carpeta raíz')->required()->trim()->check_root_callback()
                ->setRule('url', 'URL de OpenCart')->required()->valid_url()->check_url_callback()
                ->setRule('name', 'Name')->required()->trim()->regex_match('/^[\w\sáéíóúÁÉÍÓÚñÑüÜ]{3,64}$/i')
                ->setRule('code', 'Code')->required()->trim()->regex_match('/^[a-z_-]{3,64}$/')
                ->setRule('version', 'Version')->required()->trim()->regex_match('/^[1-9][0-9]{0,3}\\.(0|[1-9][0-9]{0,3})(\\.(0|[1-9][0-9]{0,3}))?$/')->max_length(32)
                ->setRule('author', 'Author')->required()->trim()->regex_match('/^[\w\sáéíóúÁÉÍÓÚñÑüÜ]{3,64}$/i')
                ->setRule('link', 'Link')->if_set()->trim()->valid_url()->max_length(255);

            if ($f->run()) {
                $key = md5(Post::name());

                $cfg = App::Config();
                $cfg->projects = [
                    $key => [
                        'projectName' => Post::projectName(),
                        'root_path' => Post::root_path(),
                        'URL' => Post::url(),
                        'zipFilename' => Post::zipFilename(),
                        'name' => Post::name(),
                        'code' => Post::code(),
                        'version' => Post::version(),
                        'author' => Post::author(),
                        'link' => Post::link(),
                        'updateCache' => true,
                        'lastPath' => '/admin',
                        'lastPathOpened' => 0,
                        'softWraps' => false,
                        'softTabs' => false,
                        'tabSize' => 4
                    ]
                ];
                $cfg->theme = 'dracula';
                $cfg->fontSize = 12;
                $cfg->currentProject = $key;
                $cfg->update();

                redirect(''); //main
            } else
                $data = FormValidation::getSummary();
        }

        Views::Render('configure', ['errors' => $data]);
    }

    function check_root($path) {
        $path = rtrim($path, '/');
        if (!(is_dir($path) && is_dir($path . '/admin') && is_dir($path . '/catalog') && is_readable($path))) {
            return 'La carpeta raíz no existe o no tiene permisos de lectura.';
        }
    }

    function check_url($url) {
        if (substr($url, 0, 4) !== 'http')
            return 'El URL de OpenCart debe ser http o https.';

        $curl = curl_init();

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
        /*echo '<pre>';
        print_r($cinfo);
        echo '</pre>';*/
        if (floor($cinfo['http_code'] / 100) !== 2 && $cinfo['http_code'] !== 301)
            return 'No se ha podido acceder al URL de OpenCart.';
    }
}
