<?php

class FilesModel {
    private $isLinux = false;

    public function __construct() {
        $this->isLinux = strtolower(PHP_OS) == 'linux';
    }

    public function normalizePath($path, $trim = true): string {
        return str_replace(['\\', '/'], DS, $trim ? trim($path, '/\\') : $path);
    }

    /*public function getUploadDirs(&$list, $path): bool {
        if ($d = @opendir(PATH_UPLOAD . DS . $path)) {
            while ($f = readdir($d)) {
                if ($f != '.' && $f != '..') {
                    $spath = $path . DS . $f;

                    if (is_dir(PATH_UPLOAD . DS . $spath)) {
                        if (!isset($list[$spath]))
                            $list[$spath] = 0;
                        if ($this->getUploadDirs($list, $spath) === false)
                            return false;
                        if (isset($list[$path]))
                            $list[$path] += $list[$spath];
                    } else {
                        if (isset($list[$path]))
                            $list[$path]++;
                    }
                }
            }
            closedir($d);

            return true;
        }

        return false;
    }*/

    public function getUploadFiles(&$list, $path) {
        if ($d = @opendir(PATH_UPLOAD . $path)) {
            while ($f = readdir($d)) {
                if ($f != '.' && $f != '..') {
                    $spath = $path . DS . $f;

                    if (is_dir(PATH_UPLOAD . $spath)) {
                        if ($this->getUploadFiles($list, $spath) === false)
                            return false;
                    } else {
                        $list[] = $spath;
                    }
                }
            }

            closedir($d);

            return true;
        }

        return false;
    }

    public function getTree() {
        $o = new StdClass();
        $o->n = App::project()['name'];
        $o->c = [];

        $folderTree = [$o];

        if (!$this->runPath($folderTree[0], '', SOURCE_ROOT_PATH))
            return FALSE;

        return $folderTree;
    }

    private function runPath(&$leaf, $path, $basePath, $processUpload = true) {
        //        static $count = 0;
        //        if ($count++ > 400)
        //            return true;

        $path = trim($path, '/\\');

        $ocmodPath = PATH_OCMOD . $path . DS;
        $uploadPath = PATH_UPLOAD . $path;

        $ocmod = 0;
        $upload = 0;
        $newFolders = 0;

        if (($dirContents = @scandir($basePath . DS . $path)) === false)
            return false;

        foreach ($dirContents as $f) {
            if ($f[0] != '.') {
                $spath = $path . DS . $f;

                if (is_dir($basePath . DS . $spath)) {
                    $o = new StdClass();
                    $o->n = $this->isLinux ? $f : utf8_encode($f);
                    $o->c = [];

                    if (!$processUpload) {
                        $newFolders++;
                        $o->new = true;
                    }

                    $leaf->c[] = $o;

                    if ($ret = $this->runPath($o, $spath, $basePath)) {
                        $ocmod += $ret[0];      //Aumentar la cantidad de peticiones de cambios (que existen en projects/xxx/ocmod)
                        $upload += $ret[1];     //Aumentar la cantidad de archivos nuevos (que existen en projects/xxx/upload)
                        $newFolders += $ret[2]; //Aumentar la cantidad de carpetas nuevas (que existen en projects/xxx/upload)
                    } else
                        return FALSE;
                } else {
                    if (is_file($ocmodPath . $f))    //OCMod?, existe en projects/xxx/ocmod
                        $ocmod++;

                    if (!$processUpload)
                        $upload++;
                }
            }
        }

        if ($processUpload && is_dir($uploadPath)) {
            if (($newDirs = @scandir($uploadPath)) === false)
                return false;

            $newDirs = array_diff($newDirs, $dirContents);

            foreach ($newDirs as $f) {
                $spath = $path . DS . $f;

                if (is_dir(PATH_UPLOAD . $spath)) {
                    $newFolders++;

                    $o = new StdClass();
                    $o->n = $this->isLinux ? $f : utf8_encode($f);
                    $o->c = [];
                    $o->new = true;
                    $leaf->c[] = $o;
                    if ($ret = $this->runPath($o, $spath, trim(PATH_UPLOAD, '\\/'), false)) {
                        $ocmod += $ret[0];
                        $upload += $ret[1];
                        $newFolders += $ret[2];
                    } else
                        return FALSE;
                } else {
                    $upload++;
                }
            }
        }

        if ($ocmod)
            $leaf->o = $ocmod;
        if ($upload)
            $leaf->u = $upload;
        if ($newFolders)
            $leaf->f = $newFolders;

        return [$ocmod, $upload, $newFolders];
    }

    public function getFileList($path) {
        $path = $this->normalizePath($path);
        $storagePath = PATH_STORAGE . $path . DS;
        $ocmodPath = PATH_OCMOD . $path;
        $uploadPath = PATH_UPLOAD . DS . $path;

        $ret = [];
        $names = [];

        //Append new files (upload)
        if (($files = @scandir($uploadPath)) !== false) {
            foreach ($files as $f) {
                $sPath = $uploadPath . DS . $f;

                if ($f[0] != '.' && is_file($sPath)) {
                    $names[] = $f;

                    $o = new StdClass();
                    $o->n = $this->isLinux ? $f : utf8_encode($f);   //Nombre
                    $o->m = is_file($storagePath . $f);      //Modificado?, se copió para DIR_STORAGE/modification
                    $o->u = true;                                    //Es un archivo nuevo

                    $ret[] = $o;
                }
            }
        }

        //Append files with changes requests
        if (($files = @scandir($ocmodPath)) !== false) {
            foreach ($files as $f) {
                $sPath = $ocmodPath . DS . $f;

                if ($f[0] != '.' && is_file($sPath) && !in_array($f, $names)) {
                    $names[] = $f;

                    $o = new StdClass();
                    $o->n = $this->isLinux ? $f : utf8_encode($f);   //Nombre
                    $o->m = is_file($storagePath . $f);      //Modificado?, existe en DIR_STORAGE/modification
                    $o->o = true;                                    //Es una petición de cambios

                    $ret[] = $o;
                }
            }
        }

        if (($files = @scandir(SOURCE_ROOT_PATH . $path)) !== false) {
            foreach ($files as $f) {
                $sPath = SOURCE_ROOT_PATH . $path . DS . $f;

                if ($f[0] != '.' && is_file($sPath) && !in_array($f, $names)) {
                    $o = new StdClass();
                    $o->n = $this->isLinux ? $f : utf8_encode($f);   //Nombre
                    $o->m = is_file($storagePath . $f);      //Modificado?, existe en DIR_STORAGE/modification

                    $ret[] = $o;
                }
            }
        }

        usort($ret, function ($a, $b) {
            return strcmp(strtolower($a->n), strtolower($b->n));
        });

        return ['files' => $ret];
    }

    public function newDir($relPath, $folderName) {
        $relPath = trim($relPath, " /\\");

        if (strpos($relPath, '/..') !== false)
            return 'La ruta no es válida.';

        $fullPath = $this->normalizePath(PATH_UPLOAD . $relPath . DS . $folderName);

        if (is_dir($fullPath) || is_dir(SOURCE_ROOT_PATH . $relPath . DS . $folderName))
            return 'Ya existe una carpeta con el nombre especificado.'; //Ya existe una carpeta con el nombre especificado

        if (@mkdir($fullPath, 0777, true))
            return true; //Ok

        return 'No ha sido posible crear la carpeta.';
    }

    public function delDir($relPath) {
        $relPath = trim($relPath, " /\\");

        if (strpos($relPath, '/..') !== false)
            return 'La ruta no es válida.';

        if (!$relPath)
            return 'No se puede eliminar la carpeta raíz.';

        $relPath = PATH_UPLOAD . DS . $relPath;

        if (!is_dir($relPath))
            return 'La carpeta que desea eliminar no existe.'; //No existe la ruta

        if (@rmdir($relPath))
            return true;

        return 'No ha sido posible eliminar la carpeta.';
    }

    public function delTree($dir, $delRoot = false) {
        if (($contents = @scandir($dir)) !== false) {
            $files = array_diff($contents, array('.', '..'));

            foreach ($files as &$file) {
                $path = $dir . DS . $file;
                if (!(is_dir($path) ? $this->delTree($path, true) : unlink($path)))
                    return false;
            }
        } else
            return false;

        return $delRoot ? rmdir($dir) : true;
    }

    public function renDir($relPath, $newName) {
        $relPath = $this->normalizePath(trim($relPath, " /\\"));
        if (!$relPath)
            return 'La ruta no es válida'; //Es el directorio raíz

        $relPath = PATH_UPLOAD . $relPath;
        $relNewPath = dirname($relPath) . DS . $newName;

        if (!is_dir($relPath))
            return 'La carpeta no existe.';

        if (strtolower($relPath) == strtolower($relNewPath))
            return 'Se ha especificado el mismo nombre.';

        if (is_dir($relNewPath))
            return 'Ya existe una carpeta con el nombre especificado.';

        if (@rename($relPath, $relNewPath))
            return true;

        return 'No ha sido posible renombrar la carpeta.';
    }

    public function renameFile($relPath, $filename, $newName) {
        $relPath = $this->normalizePath(trim($relPath, " /\\"));

        if (preg_match('[\\/:*|<>"?]', $filename . $newName))
            return 'El nombre no es válido.';

        $uploadFile = PATH_UPLOAD . $relPath . DS . $filename;
        $storageFile = PATH_STORAGE . $relPath . DS . $filename;

        $newUploadFile = PATH_UPLOAD . $relPath . DS . $newName;
        $newStorageFile = PATH_STORAGE . $relPath . DS . $newName;

        if (!file_exists($uploadFile))
            return 'El archivo no existe.';

        if (strcasecmp($uploadFile, $newUploadFile) !== 0)
            return 'El nombre nuevo es el mismo que el actual.';

        if (@rename($uploadFile, $newUploadFile)) {
            @rename($storageFile, $newStorageFile);
            return true;
        }

        return 'No ha sido posible cambiar el nombre del archivo.';
    }

    public function deleteFile($relPath, $filename) {
        $uploadFile = $this->normalizePath(PATH_UPLOAD . $relPath . DS . $filename);
        $ocmodFile = $this->normalizePath(PATH_OCMOD . $relPath . DS . $filename);
        $storageFile = $this->normalizePath(PATH_STORAGE . $relPath . DS . $filename);

        $deleted = false;
        if (file_exists($uploadFile)) {
            $deleted = @unlink($uploadFile);
        } else {
            if (file_exists($ocmodFile))
                $deleted = @unlink($ocmodFile);
        }

        if ($deleted) {
            if (file_exists($storageFile))
                @unlink($storageFile);
        }

        return $deleted
            ? true
            : 'No ha sido posible eliminar el archivo.';
    }
}