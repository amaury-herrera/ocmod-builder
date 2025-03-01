<?php
define('DS', '');
define('CACHE_FILE', '');
define('SOURCE_ROOT_PATH', '');

define('PATH_STORAGE', '');
define('PATH_OCMOD', '');
define('PATH_UPLOAD', '');
define('PATH_TEMP', '');
define('OPENCART_URL', '');

class App {
    public static function project() {
        return [
            "name" => "",
            "code" => "",
            "version" => "",
            "link" => "",
            "author" => "",
            "zipFilename" => "",
            "updateCache" => "",
            "lastPath" => "",
            "lastPathOpened" => "",
            "path" => "",
            "URL" => "",
        ];
    }

    public static function currentProject() {
        return '';
    }

    public static function Config() {
        return new ConfigManager('');
    }
}

class ConfigManager {
    public $projects;
    public $currentProject;
}
