<?php

class ConfigCheck {
    function check() {
        App::Config($cfg = new ConfigManager('config/config.json'));

        define('DS', DIRECTORY_SEPARATOR);

        $key = $cfg->currentProject;

        if ($key) {
            $projects = $cfg->projects;
            if (!array_key_exists($key, $projects)) {
                $key = key($projects);
                if ($key) {
                    $cfg->currentProject = $key;
                    $cfg->update();
                }
            }
        }

        if ($key) {
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
        } else {
            //Actualizar la configuración con lo básico
            $mustUpdate = false;
            if (is_null($cfg->theme)) $cfg->theme = $mustUpdate = "vibrant_ink";
            if (is_null($cfg->fontSize)) $cfg->fontSize = $mustUpdate = 13;
            if (is_null($cfg->softWraps)) $cfg->softWraps = $mustUpdate = 1;
            if (is_null($cfg->softTabs)) $cfg->softTabs = $mustUpdate = 1;
            if (is_null($cfg->tabSize)) $cfg->tabSize = $mustUpdate = 4;
            if (is_null($cfg->showLineNumbers)) $cfg->showLineNumbers = $mustUpdate = 1;

            if ($mustUpdate !== false)
                $cfg->update();
        }
    }
}