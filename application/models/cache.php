<?php
/*
 * Las hojas del árbol de directorio pueden contener los siguientes datos:
 * o: Cantidad de archivos con cambios (en la carpeta projects/xxxx/ocmod)
 * u: Cantidad de archivos nuevos (upload)
 * f: Cantidad de carpetas nuevas (upload)
 * new: Indica que la carpeta es nueva (upload)
 */

final class CacheModel {
    //    private $option = JSON_PRETTY_PRINT;
    private $option = null;

    public function recreate($echo = false) {
        @file_put_contents(CACHE_FILE, $json = json_encode(MODEL::Files()->getTree(), $this->option));

        $this->setUpdateConfig(false);

        if ($echo)
            echo $json;
    }

    public function recreateIfNeeded() {
        $project = App::project();
        if ($project && (!file_exists(CACHE_FILE) || !empty($project['updateCache']))) {
            $this->recreate();
        }
    }

    public function setUpdateConfig($update) {
        $cfg = App::Config();
        $projects = $cfg->projects;
        $projects[App::currentProject()]['updateCache'] = $update;
        $cfg->projects = $projects;
        $cfg->update();
    }

    public function update($path, callable $callback): bool {
        $contents = @file_get_contents(CACHE_FILE);

        if ($contents !== false) {
            $cache = @json_decode($contents, true);
            if (is_array($cache)) {
                $path = trim(MODEL::Files()->normalizePath($path), DS);

                $leaves = $this->findLeave($path, $cache);
                if ($leaves !== false) {
                    $callback($leaves);
                    return file_put_contents(CACHE_FILE, json_encode($cache, $this->option)) !== false;
                }
            }
        }

        return false;
    }

    private function _updateLeave(callable $callback, &$leaf) {
        $callback($leaf);

        //if (isset($leaf['c'])) {
        foreach ($leaf['c'] as &$v) {
            $this->_updateLeave($callback, $v);
        }
        //}
    }

    public function updateTree(callable $callback) {
        $contents = @file_get_contents(CACHE_FILE);

        if ($contents !== false) {
            $cache = @json_decode($contents, true);
            if (is_array($cache)) {
                $this->_updateLeave($callback, $cache[0]);

                return file_put_contents(CACHE_FILE, json_encode($cache, $this->option)) !== false;
            }
        }

        return false;
    }

    public function findLeave($path, &$cache) {
        $dirs = explode(DS, $path);
        $leaf = &$cache[0];
        $leaves = [&$leaf];
        foreach ($dirs as $dir) {
            foreach ($leaf['c'] as &$child) {
                if ($child['n'] == $dir) {
                    $leaves[] = &$child;
                    $leaf = &$child;
                    continue 2;
                }
            }
            return false;
        }

        return $leaves ? $leaves : false;
    }
}

