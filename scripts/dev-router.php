<?php
// Router for the PHP built-in server.
// - The server binds the wildcard address (needed for port 80 without root on
//   macOS), so refuse non-local clients.
// - Dispatches PHP scripts itself to provide the server variables that
//   WoltLab Suite expects from a regular web server (e.g. QUERY_STRING).
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1', '::ffff:127.0.0.1'], true)) {
    http_response_code(403);
    echo 'Forbidden';
    return true;
}

$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
$uriPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$file = $docRoot . $uriPath;
if (is_dir($file)) {
    if (!str_ends_with($uriPath, '/')) {
        header('Location: ' . $uriPath . '/' . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''), true, 301);
        return true;
    }
    $file = rtrim($file, '/') . '/index.php';
    $uriPath = rtrim($uriPath, '/') . '/index.php';
}
if (str_ends_with($file, '.php') && is_file($file)) {
    $_SERVER['QUERY_STRING'] ??= '';
    $_SERVER['SCRIPT_FILENAME'] = $file;
    $_SERVER['SCRIPT_NAME'] = $uriPath;
    $_SERVER['PHP_SELF'] = $uriPath;
    chdir(dirname($file));
    require $file;
    return true;
}
return false;
