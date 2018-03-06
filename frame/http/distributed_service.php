<?php

/**
 * if is https.
 *
 * @return bool
 */
function is_https()
{
    if (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] == 1)) {
        return true;
    }

    if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') {
        return true;
    }

    return false;
}

/**
 * Get the current URI.
 *
 * @return string
 */
function uri()
{
    $url = is_https() ? 'https://' : 'http://';

    return $url.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
}

/**
 * Get the specified URI info.
 *
 * @param string $name
 *
 * @return mixed
 */
function uri_info($name = null)
{
    static $container = [];

    if (empty($container)) {
        $url = uri();

        $container = parse_url($url);
    }

    return (null === $name) ? $container : $container[$name];
}

function service($path, $action)
{
    if ('/'.$path === uri_info('path')) {

        service_action($action);
        exit;
    }
}

function service_action($action)
{
    try {
        $res = unit_of_work(function () use ($action) {
            return $action(...service_args());
        });
        header('Content-type: text/plain');
        echo service_data_serialize($res);
        flush();
    } catch (Exception $ex) {
        service_ex_serialize($ex);
    }
}

function service_args()
{
    $args = unserialize(file_get_contents('php://input'));

    $returns = [];
    foreach ($args as $arg) {

        if ($arg instanceof entity && false === $arg instanceof null_entity) {
            $arg = call_user_func([get_class($arg).'_dao', 'find_by_id'], $arg->id);
        }

        $returns[] = $arg;
    }

    return $returns;
}

function service_data_serialize($data)
{
    return serialize([
        'res' => true,
        'data' => $data,
    ]);
}

function service_ex_serialize($ex)
{
    echo serialize([
        'res' => false,
        'exception_class' => get_class($ex),
        'exception_message' => $ex->getMessage(),
    ]);
    flush();
}

function service_err_serialize($error_type, $error_message, $error_file, $error_line, $error_context = null)
{
    $message = $error_message.' '.$error_file.' '.$error_line;

    service_ex_serialize(new Exception($message));
}

function service_fatel_err_serialize()
{
    $err = error_get_last();

    if (not_empty($err)) {
        service_err_serialize($err['type'], $err['message'], $err['file'], $err['line']);
    }
}

function service_method_not_found()
{
    throw new Exception('调用了不存在的 service 方法');
}
