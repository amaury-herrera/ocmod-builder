<?php
define("TAG_OPERATION_BEGIN", "<OCMOD>");
define("TAG_OPERATION_END", "</OCMOD>");
define("TAG_SEARCH_BEGIN", "<search");
define("TAG_SEARCH_END", "</search>");
define("TAG_ADD_BEGIN", "<add");
define("TAG_ADD_END", "</add>");

class OCMODModel {
    private $changedFiles = [];
    private $xml = '';
    public $errors = [];

    public function getXML() {
        return $this->xml;
    }

    public function getErrors() {
        return $this->errors;
    }

    /**
     * Procesa el contenido de un archivo en busca de bloques OCMOD.
     * @param $text
     * Contenido del archivo a procesar
     * @param $fileName
     * Nombre del archivo (solo para incluir en el texto de los errores)
     * @return bool
     * Devuelve false si no se encuentra ningún bloque OCMOD y true si contiene al menos un bloque, sean o no válidos.
     * Si hay errores devuelve true y se reflejan en MODEL::OCMOD()->errors.
     */
    public function processContent($text, $fileName): bool {
        $fileName = trim(str_replace(PATH_OCMOD, '', $fileName), '/\\');

        $commentsBegin = ['//', '/*', '<!--', '{#'];
        $commentsEnd = ['*/', '-->', '#}'];

        $operations = '';

        $end = -1;
        while (false !== ($begin = strpos($text, TAG_OPERATION_BEGIN, $end + 1))) {
            $end = strpos($text, TAG_OPERATION_END, $begin + 1);
            if (false === $end) {
                $this->errors[] = "Falta el marcador de cierre en " . $fileName;
                return true;
            }
            $search = false;
            $searchEnd = $begin;
            while (false !== ($searchBegin = strpos($text, TAG_SEARCH_BEGIN, $searchEnd + 1)) and $searchBegin < $end) {
                $searchBeginR = strpos($text, '>', $searchBegin + 1);
                $searchAttributes = substr($text, $searchBegin + strlen(TAG_SEARCH_BEGIN), $searchBeginR - $searchBegin - strlen(TAG_SEARCH_BEGIN));
                if (false === $searchBeginR or $searchBeginR >= $end) {
                    $this->errors[] = "Etiqueta search no válida en " . $fileName;
                    return true;
                }
                $searchEnd = strpos($text, TAG_SEARCH_END, $searchBeginR + 1);
                if (false === $searchEnd or $searchEnd >= $end) {
                    $this->errors[] = "Etiqueta search sin cerrar en " . $fileName;
                    return true;
                }

                $search = substr($text, $searchBeginR + 1, $searchEnd - $searchBeginR - 1);
            }
            $addBegin = strpos($text, TAG_ADD_BEGIN, $begin + 1);
            if (false === $addBegin or $addBegin >= $end) {
                $this->errors[] = "No hay etiqueta add en " . $fileName;
                return true;
            }
            $addBeginR = strpos($text, '>', $addBegin + 1);
            $addAttributes = substr($text, $addBegin + strlen(TAG_ADD_BEGIN), $addBeginR - $addBegin - strlen(TAG_ADD_BEGIN));
            if (false === $addBeginR or $addBeginR >= $end) {
                $this->errors[] = "Etiqueta add no válida en " . $fileName;
                return true;
            }
            $addEnd = strpos($text, TAG_ADD_END, $addBeginR + 1);
            if (false === $addEnd or $addEnd >= $end) {
                $this->errors[] = "Etiqueta add sin cerrar en " . $fileName;
                return true;
            }
            $codeBegin = $addBeginR + 1;
            $codeEnd = $addEnd;

            $p = $codeBegin;
            while (@$text[$p] === " " or @$text[$p] === "\t" or @$text[$p] === "\r" or @$text[$p] === "\n")
                $p++;
            if ($p < $addEnd) {
                foreach ($commentsEnd as &$tag)
                    if (substr($text, $p, strlen($tag)) === $tag)
                        $codeBegin = $p + strlen($tag);
            }
            $p = $codeEnd - 1;
            while (@$text[$p] === " " or @$text[$p] === "\t" or @$text[$p] === "\r" or @$text[$p] === "\n")
                $p--;
            if ($p >= $codeBegin) {
                foreach ($commentsBegin as &$tag)
                    if (substr($text, $p - strlen($tag) + 1, strlen($tag)) === $tag)
                        $codeEnd = $p - strlen($tag) + 1;
            }

            $code = substr($text, $codeBegin, $codeEnd - $codeBegin - 1);

            if (strpos($addAttributes, 'LTRIM') !== false) {
                $code = ltrim($code);
                $addAttributes = str_replace(['  ', ' >'], [' ', '>'], str_replace('LTRIM', '', $addAttributes));
            }
            if (strpos($addAttributes, 'RTRIM') !== false) {
                $code = rtrim($code);
                $addAttributes = str_replace(['  ', ' >'], [' ', '>'], str_replace('RTRIM', '', $addAttributes));
            }
            if (strpos($addAttributes, 'TRIM') !== false) {
                $code = trim($code);
                $addAttributes = str_replace(['  ', ' >'], [' ', '>'], str_replace('TRIM', '', $addAttributes));
            }

            if (preg_match('/APPEND="([^"]*)"/', $addAttributes, $m)) {
                $addAttributes = str_replace($m[0], '', $addAttributes);
                $code .= $m[1];
            }

            if (preg_match('/PREPEND="([^"]*)"/', $addAttributes, $m)) {
                $addAttributes = str_replace($m[0], '', $addAttributes);
                $code = $m[1] . $code;
            }

            $addAttributes = trim($addAttributes);
            if ($addAttributes)
                $addAttributes = ' ' . $addAttributes;

            if ($operations)
                $operations .= "\r\n    <!-- ========================================== -->";

            $operations .= "
    <operation>" . (false !== $search ? "
      <search{$searchAttributes}>
        <![CDATA[{$search}]]>
      </search>" : "") . "
      <add{$addAttributes}>
        <![CDATA[{$code}]]>
      </add>
    </operation>";
        }

        if ($operations) {
            if (!in_array($fileName, $this->changedFiles))
                $this->changedFiles[] = $fileName;

            if (substr($this->xml, -7) == '</file>')
                $this->xml .= "\r\n";
            $this->xml .= "
  <file path=\"" . str_replace('\\', '/', $fileName) . "\">{$operations}
  </file>";
            return true;
        }

        return false;
    }

    public function processFile($fileName): bool {
        return $this->processContent(file_get_contents($fileName), $fileName);
    }

    function processDir($dir) {
        $cdir = scandir($dir);
        foreach ($cdir as &$value) {
            if (in_array($value, [".", ".."]))
                continue;

            $fileName = $dir . DS . $value;

            if (is_dir($fileName))
                $this->processDir($fileName);
            else
                $this->processFile($fileName);
        }
    }

    function addFolderToZip($zip, $dir) {
        $dir = trim($dir, '/\\') . DIRECTORY_SEPARATOR;

        $cdir = scandir($dir);
        foreach ($cdir as &$file) {
            if (in_array($file, array(".", "..")))
                continue;

            $file = $dir . $file;

            if (is_dir($file))
                addFolderToZip($zip, $file);
            else
                $zip->addFile($file, $file/*str_replace('publish' . DIRECTORY_SEPARATOR, '', $file)*/);
        }
    }

    public function generateXML() {
        $proj = App::project();
        $code = App::currentProject();

        $this->changedFiles = [];
        $this->errors = [];

        $this->processDir(trim(PATH_OCMOD, '\\/'));

        $this->xml = trim("<?xml version=\"1.0\" encoding=\"utf-8\"?>
<modification>
  <name>{$proj['name']}</name>
  <code>{$code}</code>
  <version>{$proj['version']}</version>
  <author>{$proj['author']}</author>");

        if ($proj['link'])
            $this->xml .= "  <link>" . $proj['link'] . "</link>";

        foreach ($this->changedFiles as $file) {
            $this->processFile(PATH_OCMOD . $file, '');
        }

        $this->xml .= "
</modification>";

        return $this;
    }

    public function createZip($zipFilename = '') {
        $zips = glob(PATH_TEMP . '*.*');
        foreach ($zips as $zip)
            @unlink($zip);

        if ($zipFilename) {
            if (substr($zipFilename, -10) != '.ocmod.zip')
                $zipFilename .= '.ocmod.zip';
            $zipFilename = PATH_TEMP . $zipFilename;
        } else {
            $zipFilename = tempnam(PATH_TEMP, 'tmp');
            if ($zipFilename === false)
                return false;
        }

        $this->generateXML();

        try {
            $zip = new ZipArchive();

            if ($zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                if ($this->changedFiles)
                    $zip->addFromString('install.xml', $this->xml);

                //            if (!empty($sql))
                //                $zip->addFile('install.sql', basename($sql));

                $uploadFiles = [];
                MODEL::Files()->getUploadFiles($uploadFiles, '');

                if (!($this->changedFiles || $uploadFiles)) {
                    return 'No hay nada que instalar.';
                }

                // Agregar archivos de la carpeta upload
                foreach ($uploadFiles as &$file) {
                    $zip->addFile(PATH_UPLOAD . $file, 'upload' . str_replace('\\', '/', $file));
                }

                @$zip->close();

                return [
                    'zipFilename' => $zipFilename,
                    'errors' => $this->errors,
                ];
            }
        } catch (Exception $e) {
            return false;
        }
    }
}