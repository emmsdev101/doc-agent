<?php

$publicPath = getcwd();

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
$file = $publicPath.$uri;

if ($uri !== '/' && is_file($file)) {
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $corsTypes = ['js', 'mjs', 'css', 'map', 'woff', 'woff2'];

    if (in_array($extension, $corsTypes, true)) {
        $mimes = [
            'js' => 'text/javascript; charset=utf-8',
            'mjs' => 'text/javascript; charset=utf-8',
            'css' => 'text/css; charset=utf-8',
            'map' => 'application/json',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
        ];

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: *');
        header('Cross-Origin-Resource-Policy: cross-origin');
        header('Content-Type: '.$mimes[$extension]);
        header('Content-Length: '.filesize($file));

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        readfile($file);
        exit;
    }

    return false;
}

$formattedDateTime = date('D M j H:i:s Y');
$requestMethod = $_SERVER['REQUEST_METHOD'];
$remoteAddress = $_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'];
file_put_contents('php://stdout', "[$formattedDateTime] $remoteAddress [$requestMethod] URI: $uri\n");

require_once $publicPath.'/index.php';
