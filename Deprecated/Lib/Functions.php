<?php

/**
 * Obtains main url
 *
 * @return string
 */
function getUrl()
{
    $ssl = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on';
    $proto = strtolower($_SERVER['SERVER_PROTOCOL']);
    $proto = substr($proto, 0, strpos($proto, '/')) . ($ssl ? 's' : '');
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
    } else {
        $port = $_SERVER['SERVER_PORT'];
        $port = ((!$ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':' . $port;
        $host = $_SERVER['SERVER_NAME'] . $port;
    }

    $script = $_SERVER['SCRIPT_NAME'];

    $script = substr($script, 0, strlen($script) - strlen('/index.php'));
    return $proto . '://' . $host . $script;
}
