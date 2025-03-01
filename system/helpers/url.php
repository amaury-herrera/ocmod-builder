<?php
/**
 * Genera un URL para HREF o SRC.
 * @param string $path
 * Si $path comienza con ~, se sustituye por //dominio/controlador/funcion
 * Si $path comienza con @, se sustituye por //dominio/carpeta
 * Si $path comienza con ^, se sustituye por //dominio
 * Colocar ! al inicio si no se desea alterar la ruta especificada
 * @param bool $ret
 * @return string
 */
function href($path = '', $ret = false) {
    if ($path) {
        if ($path[0] == '~')
            $path = APP_DOMAIN . App::$CONTROLLER . (App::$FUNCTION != Config::$defaultFunction ? '/' . App::$FUNCTION : '') . substr($path, 1);
        else if ($path[0] == '@')
            $path = APP_DOMAIN . substr($path, 1);
        else if ($path[0] == '^')
            $path = APP_HOST . substr($path, 1);
        else if ($path[0] == '!')
            $path = substr($path, 1);
        else if (!preg_match('/^((http[s]?:)?\/\/)/im', $path))
            $path = APP_DOMAIN . trim($path, '/');
    } else
        $path = APP_DOMAIN;

    //if (Config::$relativeURLs && substr($path, 0, $ln = strlen(APP_DOMAIN)) == APP_DOMAIN)
    //$path = substr($path, $ln - 1);

    if ($ret)
        return $path;

    echo $path;
}

/**
 * Genera una etiqueta SCRIPT a la URI especificada. La URI sigue las mismas reglas de href()
 * @param $uri
 * URI del script
 * @param string $id
 * Id del elemento
 * @param bool $ret
 * Especificar a true para devolverla en lugar de enviarla al navegador (por defecto)
 * @return string
 */
function script($uri, $id = '', $ret = false) {
    $r = '<script type="application/javascript" src="' . href($uri, true) . '?v=' . Config::$app_version . '"' . ($id ? " id=\"{$id}\"" : '') . '></script>' . "\n";
    if ($ret)
        return $r;
    echo $r;
}

/**
 * Genera una etiqueta SCRIPT con el contenido especificado
 * @param $content
 * Contenido del script
 * @param bool $ret
 * Especificar a true para devolverla en lugar de enviarla al navegador (por defecto)
 * @return string
 */
function javascript($content, $ret = false) {
    $r = "<script type=\"application/javascript\">\n{$content}\n</script>\n";
    if ($ret)
        return $r;
    echo $r;
}

/**
 * Genera una etiqueta LINK para hoja de estilos a la URI especificada.
 * La URI sigue las mismas reglas de href()
 * @param $uri
 * URI del script
 * @param string $id Id del elemento
 * @param $ret
 * Especificar a true para devolverla en lugar de enviarla al navegador (por defecto)
 * @return string
 */
function styleSheet($uri, $id = '', $ret = false) {
    $r = '<link ' . ($id ? "id=\"{$id}\" " : '') . 'rel="stylesheet" href="' . href($uri, true) . '?v=' . Config::$app_version . '"/>' . "\n";
    if ($ret)
        return $r;
    echo $r;
}

/*
 * Genera un URL que apunta a la carpeta pública. Resultado: //domain/public
 */
function asset($path = '', $ret = false) {
    if ($path && !preg_match('/^((http[s]?:)?\/\/)/im', $path))
        $path = PUBLIC_FOLDER . trim($path, '/');

    if ($ret)
        return $path;

    echo $path;
}

/*
 * Produce una etiqueta A. La ruta sigue las mismas reglas que href()
 */
function anchor($uri = '', $title = '', $attributes = '') {
    $uri = href($uri, true);

    if ((string)$title == '')
        $title = $uri;

    if ($attributes != '')
        $attributes = _parse_attributes($attributes);

    return '<a href="' . $uri . '"' . $attributes . '>' . $title . '</a>';
}

/*
 * Produce una etiqueta A con un correo como href
 */
function mailto($email, $title = '', $attributes = '') {
    $title = (string)$title;

    if ($title == "")
        $title = $email;

    $attributes = _parse_attributes($attributes);

    return '<a href="mailto:' . $email . '"' . $attributes . '>' . $title . '</a>';
}

/**
 * Redirecciona a la página especificada
 * @param string $uri
 * Dirección a donde redireccionar. Si es ~ se sustituye por //dominio/controlador/función
 * @param string $method
 * Puede ser refresh o location (implícito)
 * @param int $http_response_code
 * Código de respuesta, por defecto 302
 */
function redirect($uri = '', $method = 'location', $http_response_code = 302) {
    $uri = href($uri, true);

    switch ($method) {
        case 'refresh':
            header("Refresh:0;url=" . $uri);
            break;
        default:
            header("Location: " . $uri, TRUE, $http_response_code);
            break;
    }
    exit;
}

function access_denied() {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

function noCache() {
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: max-age=0, no-store, no-cache, must-revalidate');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
}

function doCache($etag, $timeSecs = 3600) {
    $exp = date('D, j M Y H:i:s T', time() + $timeSecs);

    if (!$etag)
        $etag = md5(href('', true));

    header("Expires: $exp");
    header("If-Modified-Since: Mar 16 2007");
    header("Cache-Control: public, maxage=$timeSecs");
    header('Pragma: cache');
    header("ETag: $etag");
    header('Last-Modified: Fri, 9 Jul 2010 10:51:00 GMT');
}

function _parse_attributes($attributes, $javascript = FALSE) {
    if (is_string($attributes))
        return ($attributes == '') ? '' : ' ' . $attributes;

    $att = '';
    foreach ($attributes as $key => $val)
        $att .= $javascript ? ($key . '=' . $val . ',') : (' ' . $key . '="' . $val . '"');

    if ($javascript and $att != '')
        $att = substr($att, 0, -1);

    return $att;
}