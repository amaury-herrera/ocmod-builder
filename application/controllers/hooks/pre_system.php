<?php

class ConfigCheck {
    function check() {
        App::Config($cfg = new ConfigManager('config/config.json'));

        define('DS', DIRECTORY_SEPARATOR);

        if ($projects = $cfg->projects) {
            if (!array_key_exists($cfg->currentProject, $projects)) {
                $cfg->currentProject = key($projects);
                $cfg->update();
            }

            $key = $cfg->currentProject;

            App::currentProject($key);
            App::project($cfg->projects[$key]);

            define('OPENCART_URL', App::project()["URL"]);
            define('SOURCE_ROOT_PATH', rtrim(App::project()['root_path'], '\\/') . DS);

            @include_once(SOURCE_ROOT_PATH . 'config.php');

            define('PATH_STORAGE', MODEL::Files()->normalizePath(trim(DIR_STORAGE, '/\\') . '/modification/', false));
            define('PATH_OCMOD', MODEL::Files()->normalizePath('projects/' . $key . '/ocmod/', false));
            define('PATH_UPLOAD', MODEL::Files()->normalizePath('projects/' . $key . '/upload/', false));
            define('PATH_TEMP', MODEL::Files()->normalizePath('projects/' . $key . '/temp/', false));
            define('CACHE_FILE', MODEL::Files()->normalizePath('projects/' . $key . '/cache/tree.json', false));

            @mkdir(dirname(CACHE_FILE), 0777, true);
            @mkdir(PATH_OCMOD, 0777, true);
            @mkdir(PATH_TEMP, 0777, true);
            @mkdir(PATH_UPLOAD, 0777, true);

            if (App::$CONTROLLER == 'configure')
                redirect(''); //main
        } else {
            if ((App::$CONTROLLER != 'configure' || App::$FUNCTION != 'index'))
                redirect('configure');
        }
    }
}