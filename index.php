<?php
require 'vendor/autoload.php';

use SebastianBergmann\Diff\Differ;

set_time_limit(0);
session_start();

include_once('ocmod-builder.cfg.php');
include_once(SOURCE_ROOT_PATH . '/config.php');

$lang = LANGUAGE;
if (!file_exists($langFile = 'lang/' . LANGUAGE . '.php')) {
    echo '<!DOCTYPE html>
<html lang="es">
<head>
    <title>OCMOD Builder</title>
    <meta charset="UTF-8">
</head>
<body>
    Language file not found / Archivo de idioma no encontrado.<br>
    (' . LANGUAGE . '.php)
</body>
</html>';
    exit();
}

include_once($langFile);

extract($lang, EXTR_OVERWRITE | EXTR_PREFIX_ALL, 'lang_');

define("TAG_SEARCH_BEGIN", "<search");
define("TAG_SEARCH_END", "</search>");
define("TAG_ADD_BEGIN", "<add");
define("TAG_ADD_END", "</add>");

$action = '';

$commandLine = empty($_SERVER['HTTP_HOST']);

if (!empty($_POST)) {
    if (!empty($_POST['action'])) {
        // Cadenas de texto a comparar
        $modFileExists = true;

        $srcFilename = $_POST['action'] == 'get_upload'
            ? ROOT_PATH . DIRECTORY_SEPARATOR . $_POST['file']
            : SOURCE_ROOT_PATH . DIRECTORY_SEPARATOR . $_POST['file'];

        if (file_exists($srcFilename)) {
            $sourceText = file_get_contents($srcFilename);

            switch ($_POST['action']) {
                case 'get_orig':
                    $modFilename = $srcFilename;
                    break;

                case 'get_ocmod':
                case 'get_upload':
                    $modFilename = ROOT_PATH . DIRECTORY_SEPARATOR . $_POST['file'];
                    break;

                case 'get_diff':
                    $modFilename = DIR_STORAGE . 'modification' . DIRECTORY_SEPARATOR . $_POST['file'];
                    break;

                default:
                    header('Content-Type: application/json');
                    echo json_encode(['error' => $lang__unknown_action]);
                    exit();
            }

            if ($modFileExists = file_exists($modFilename))
                $modifiedText = file_get_contents($modFilename);

            $differ = new Differ();

            // Obtener las diferencias
            $git_diff = $differ->diffToArray($sourceText, $modifiedText);

            $lines = [];
            foreach ($git_diff as $line) {
                if ($line[1] == 1)
                    $lines[] = '[+]' . rtrim($line[0]);
                elseif ($line[1] == 2)
                    $lines[] = '[-]' . rtrim($line[0]);
                else
                    $lines[] = rtrim($line[0]);
            }
        }

        $errors = [];
        if (!file_exists($srcFilename))
            $errors[] = sprintf($lang__file_not_found, $srcFilename);
        if (!$modFileExists)
            $errors[] = sprintf($lang__file_not_found, $modFilename);

        header('Content-Type: application/json');

        if (empty($errors))
            echo json_encode(['content' => $lines]);
        else
            echo json_encode(['error' => implode('<br>', $errors)]);

        return;
    }

    /*if (!(empty($_POST['get_orig']))) {
        header('Content-Type: application/json');
        echo json_encode(['content' => file_get_contents(SOURCE_ROOT_PATH . DIRECTORY_SEPARATOR . $_POST['file'])]);
        return;
    }

    if (!(empty($_POST['get_upload']))) {
        header('Content-Type: application/json');
        echo json_encode(['content' => file_get_contents(ROOT_PATH . DIRECTORY_SEPARATOR . $_POST['file'])]);
        return;
    }*/

    return;
} else {
    if (!empty($_GET)) {
        $action = @$_GET['action'];
    }
}

function processFile($fileName, $relativePath)
{
    global $upload, $changedFiles;

    if (!file_exists($fileName)) {
        echo $fileName . ' not found.<br>';
        return;
    }

    global $commentsBegin, $commentsEnd, $xml, $exclude;

    if ($exclude) {
        foreach ($exclude as &$ex)
            if (false !== strpos($relativePath, $ex))
                return;
    }

    $srcFileName = str_replace(ROOT_PATH, SOURCE_ROOT_PATH, $fileName);
    if (!file_exists($srcFileName)) {
        $newFile = str_replace('\\', '/', trim(str_replace(SOURCE_ROOT_PATH, '', $srcFileName), '/\\'));
        if (!in_array($newFile, $upload))
            $upload[] = $newFile;

        return;
    }

    $operations = '';
    $text = file_get_contents($fileName);
    $end = -1;
    while (false !== ($begin = strpos($text, TAG_OPERATION_BEGIN, $end + 1))) {
        $end = strpos($text, TAG_OPERATION_END, $begin + 1);
        if (false === $end)
            die ("No close operation tag in " . $fileName);
        $search = false;
        $searchEnd = $begin;
        while (false !== ($searchBegin = strpos($text, TAG_SEARCH_BEGIN, $searchEnd + 1)) and $searchBegin < $end) {
            $searchBeginR = strpos($text, '>', $searchBegin + 1);
            $searchAttributes = substr($text, $searchBegin + strlen(TAG_SEARCH_BEGIN), $searchBeginR - $searchBegin - strlen(TAG_SEARCH_BEGIN));
            if (false === $searchBeginR or $searchBeginR >= $end)
                die ("Invalid search tag in " . $fileName);
            $searchEnd = strpos($text, TAG_SEARCH_END, $searchBeginR + 1);
            if (false === $searchEnd or $searchEnd >= $end)
                die ("No close search tag in " . $fileName);

            $search = substr($text, $searchBeginR + 1, $searchEnd - $searchBeginR - 1);
        }
        $addBegin = strpos($text, TAG_ADD_BEGIN, $begin + 1);
        if (false === $addBegin or $addBegin >= $end)
            die ("No begin add tag in " . $fileName);
        $addBeginR = strpos($text, '>', $addBegin + 1);
        $addAttributes = substr($text, $addBegin + strlen(TAG_ADD_BEGIN), $addBeginR - $addBegin - strlen(TAG_ADD_BEGIN));
        if (false === $addBeginR or $addBeginR >= $end)
            die ("Invalid add tag in " . $fileName);
        $addEnd = strpos($text, TAG_ADD_END, $addBeginR + 1);
        if (false === $addEnd or $addEnd >= $end)
            die ("No close add tag in " . $fileName);
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
        $newFile = trim(str_replace(SOURCE_ROOT_PATH, '', $srcFileName), '/\\');
        if (!in_array($newFile, $changedFiles))
            $changedFiles[] = $newFile;

        if (substr($xml, -7) == '</file>')
            $xml .= "\r\n";
        $xml .= "
  <file path=\"" . ltrim(str_replace('"', '\"', $relativePath), '\\/') . "\">{$operations}
  </file>";
    }
}

function processDir($dir, $doExclude, $relativePath = '')
{
    global $exclude;

    $cdir = scandir($dir);
    foreach ($cdir as $key => &$value) {
        if (in_array($value, array(".", "..")))
            continue;

        $fileName = $dir . DIRECTORY_SEPARATOR . $value;

        $excluded = false;
        if ($exclude && $doExclude) {
            foreach ($exclude as &$ex) {
                if (false !== strpos($fileName, $ex)) {
                    $excluded = true;
                    break;
                }
            }

            if ($excluded)
                continue;
        }

        $newRelativePath = str_replace(['\\'], '/', str_replace(ROOT_PATH, '', $fileName));

        if (is_dir($fileName)) {
            processDir($fileName, $doExclude, $newRelativePath);
        } else {
            processFile($fileName, $newRelativePath);
        }
    }
}

function delTree($dir, $delRoot = false)
{
    $files = array_diff(scandir($dir), array('.', '..'));

    foreach ($files as &$file) {
        is_dir("$dir/$file")
            ? delTree("$dir/$file", true)
            : unlink("$dir/$file");
    }
    return $delRoot ? rmdir($dir) : true;
}

function updateConfig()
{
    global $upload, $changedFiles;

    $changedFiles = array_map(
        function ($file) {
            return str_replace('\\', '/', $file);
        },
        $changedFiles
    );

    $upload = array_map(
        function ($file) {
            return str_replace('\\', '/', $file);
        },
        array_values(
            array_filter($upload, function ($file) {
                return file_exists(ROOT_PATH . DIRECTORY_SEPARATOR . $file);
            }))
    );

    $content = file_get_contents('ocmod-builder.cfg.php');

    if ($content) {
        $content = preg_replace('/\$changedFiles\s+=\s+\[[^\]]*\]/', sprintf('$changedFiles = %s', json_encode($changedFiles, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT, 2)), $content);
        $content = preg_replace('/\$upload\s+=\s+\[[^\]]*\]/', sprintf('$upload = %s', json_encode($upload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT, 2)), $content);
        file_put_contents('ocmod-builder.cfg.php', $content);
    }
}

function addFolderToZip($zip, $dir)
{
    $dir = trim($dir, '/\\') . DIRECTORY_SEPARATOR;

    $cdir = scandir($dir);
    foreach ($cdir as &$file) {
        if (in_array($file, array(".", "..")))
            continue;

        $file = $dir . $file;

        if (is_dir($file))
            addFolderToZip($zip, $file);
        else
            $zip->addFile($file, str_replace('publish' . DIRECTORY_SEPARATOR, '', $file));
    }
}

function copyRecursive($dir)
{
    $cdir = scandir($dir);
    foreach ($cdir as &$file) {
        if ($file == '.' || $file == '..' || $file == '.idea')
            continue;

        $file = $dir . DIRECTORY_SEPARATOR . $file;

        if (is_dir($file)) {
            copyRecursive($file);
        } else {
            $dest = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, str_replace(SOURCE_ROOT_PATH, ROOT_PATH, $file));
            $file = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file);

            if (!file_exists($dest) || filesize($dest) != filesize($file)) {
                $ndir = dirname($dest);
                if (!is_dir($ndir))
                    mkdir($ndir, 0777, true);

                copy($file, $dest);
            }
        }
    }
}

if (!empty($exclude)) {
    foreach ($exclude as &$ex)
        $ex = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $ex);
}

// Nombre del archivo ZIP que se va a crear
if (empty($zipFileName))
    $zipFileName = 'modification.ocmod.zip';
else
    if (substr($zipFileName, -10) != '.ocmod.zip')
        $zipFileName .= '.ocmod.zip';

switch ($action) {
    case 'create_zip':
        if (!(empty($changedFiles) && empty($upload))) {
            $xml = trim("<?xml version=\"1.0\" encoding=\"" . ENCODING . "\"?>
<modification>
  <name>" . NAME . "</name>
  <code>" . CODE . "</code>
  <version>" . VERSION . "</version>
  <author>" . AUTHOR . "</author>
");

            if (LINK)
                $xml .= "  <link>" . LINK . "</link>";

            foreach ($changedFiles as $file) {
                processFile(ROOT_PATH . DIRECTORY_SEPARATOR . $file, '');
            }

            $xml .= "
</modification>";

            updateConfig();

            if (is_dir('publish'))
                delTree('publish');

            file_put_contents('publish/install.xml', $xml);

            if (!empty($sql))
                file_put_contents('publish/install.sql', $sql);

            if (!empty($upload)) {
                foreach ($upload as $file) {
                    $srcFile = ROOT_PATH . (@$file[0] === '/' ? '' : '/') . $file;

                    if (!file_exists($srcFile))
                        continue;

                    $dstFile = 'publish/upload' . (@$file[0] === '/' ? '' : '/') . $file;

                    if (!is_dir(dirname($dstFile)))
                        mkdir(dirname($dstFile), 0777, true);

                    copy($srcFile, $dstFile);
                }
            }

            try {
                $zip = new ZipArchive();

                if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                    $filesToAdd = scandir('publish');

                    // Agregar archivos al ZIP
                    foreach ($filesToAdd as &$file) {
                        if ($file == '.' || $file == '..')
                            continue;

                        $file = 'publish' . DIRECTORY_SEPARATOR . $file;

                        if (is_dir($file))
                            addFolderToZip($zip, $file);
                        else {
                            if (file_exists($file))
                                $zip->addFile($file, basename($file));
                        }
                    }

                    @$zip->close();

                    $_SESSION['message'] = sprintf($lang__zip_created, $zipFileName);
                }
            } catch (Exception $e) {
                $_SESSION['error'] = sprintf($lang__zip_error, $zipFileName);//$e->getMessage();
            }
        } else
            $_SESSION['error'] = sprintf($lang__nothing_to_do, $zipFileName);

        header("Location: index.php");
        exit();

    case 'restore':
        delTree('publish');
        //delTree(ROOT_PATH);
        copyRecursive(SOURCE_ROOT_PATH);

        header("Location: index.php");
        exit();

    case 'detect':
        $changedFiles = [];

        processDir(ROOT_PATH, true);

        if (!empty($force_include_dirs)) {
            foreach ($force_include_dirs as $dir) {
                processDir(trim(ROOT_PATH, '\\/') . DIRECTORY_SEPARATOR . trim($dir, '\\/'), false);
            }
        }

        updateConfig();

        sleep(3); //Dar tiempo a que cierre bien el archivo de configuración

        header("Location: index.php");
        exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>OCMOD Builder</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="<?php echo SOURCE_ROOT_PATH; ?>/catalog/view/javascript/jquery/jquery-3.7.0.min.js"></script>
    <link href="<?php echo SOURCE_ROOT_PATH; ?>/catalog/view/javascript/bootstrap/css/bootstrap.min.css" rel="stylesheet"></link>
    <script src="<?php echo SOURCE_ROOT_PATH; ?>/catalog/view/javascript/bootstrap/js/bootstrap.js"></script>
    <link href="files/prism.css" rel="stylesheet"/>
    <link href="files/styles.css" rel="stylesheet"/>
</head>
<body>
<div id="forms" style="margin-bottom: 20px">
    <form method="get" action="index.php">
        <button class="btn btn-default" name="action" value="detect" type="submit">
            <?php echo $lang__detect; ?>
        </button>
    </form>
    <form method="get" action="index.php">
        <button class="btn btn-default" name="action" value="create_zip" type="submit">
            <?php echo sprintf($lang__create_zip, basename($zipFileName)); ?>
        </button>
    </form>
    <form method="get" action="index.php" id="form_restore" class="pull-right" style="margin-right: 5px">
        <button class="btn btn-secondary" name="action" value="restore" type="submit">
            <?php echo $lang__restore; ?>
        </button>
    </form>
</div>

<div class="wrapper">
    <div id="files"><?php
        if (!empty($_SESSION['message'])) {
            echo "
            <div class=\"alert alert-success alert-dismissible\" role=\"alert\">
              <button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button>
              {$_SESSION['message']}
            </div>";

            unset($_SESSION['message']);
        }

        if (!empty($_SESSION['error'])) {
            echo "
            <div class=\"alert alert-danger alert-dismissible\" role=\"alert\">
              <button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button>
              {$_SESSION['error']}
            </div>";

            unset($_SESSION['error']);
        }

        function echoFiles($files, $isUpload = false)
        {
            global $lang__see_file, $lang__not_copied, $lang__original, $lang__modified, $lang__not_modified;

            echo "\r\n        <ul class=\"list-group\">\r\n";
            foreach ($files as &$file) {
                echo "            <li class='list-group-item'><span>{$file}</span>\r\n";

                $storage_file = DIR_STORAGE . 'modification' . DIRECTORY_SEPARATOR . $file;

                if ($isUpload) {
                    echo "                <br><a class='get_upload' href=\"{$file}\">{$lang__see_file}</a>";
                    if (!file_exists($storage_file))
                        echo "<span class=\"a\">&nbsp;&nbsp;&nbsp;&nbsp;{$lang__not_copied}</span>";
                } else {
                    echo "                <br><a class='get_orig' href=\"{$file}\">{$lang__original}</a>";
                    echo "<a class='get_ocmod' href=\"{$file}\">OCMOD</a>";

                    echo file_exists($storage_file)
                        ? "<a class='get_diff' href=\"{$file}\">{$lang__modified}</a>"
                        : "<small class=\"a\">&nbsp;&nbsp;&nbsp;&nbsp;{$lang__not_modified}</small>";
                }
                echo "\r\n            </li>\r\n";
            }
            echo "        </ul>\r\n";
        }

        $hasChanges = false;
        if (!empty($changedFiles)) {
            echo "\r\n        <h4><strong>{$lang__files_w_changes}</strong></h4>";
            echoFiles($changedFiles);
            $hasChanges = true;
        }

        if (!empty($upload)) {
            echo "\r\n        <h4><strong>{$lang__upload_files}</strong></h4>";
            echoFiles($upload, true);
            $hasChanges = true;
        }

        if (!$hasChanges)
            echo "<h3 style=\"margin: 0 0 10px 0\">{$lang__no_changes}</h3>{$lang__press_detect}";
        ?>

        <div id="alert" class="alert alert-danger alert-dismissible" style="display: none; margin-top: 20px;">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <div id="alertText"></div>
        </div>
    </div>
    <div id="content_wrapper">
        <div id="line_numbers"></div>
        <div id="file_content"></div>
    </div>
</div>

<div class="text-right">
    <span><?php echo $lang__keys_explanation; ?></span>
    <div class="btn-group" style="margin: 5px">
        <button class="btn btn-default" type="button" id="btnGoFirst">&lt;&lt;</button>
        <button class="btn btn-default" type="button" id="btnGoPrev">&lt;</button>
        <button class="btn btn-default" type="button" id="btnGoNext">&gt;</button>
        <button class="btn btn-default" type="button" id="btnGoLast">&gt;&gt;</button>
    </div>
</div>

<script type="application/javascript">
    var lang_confirm_restore = '<?php echo $lang__confirm_restore; ?>';
</script>
<script type="application/javascript" src="files/ocmod-builder.js"></script>
<script src="files/prism.js"></script>

</body>
</html>
