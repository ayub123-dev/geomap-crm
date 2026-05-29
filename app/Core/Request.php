<?php

namespace App\Core;

class Request
{
    public static function method()
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function query($key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }

        return array_key_exists($key, $_GET) ? $_GET[$key] : $default;
    }

    public static function body()
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $raw = file_get_contents('php://input');

        if ($raw === false || trim($raw) === '') {
            return $_POST;
        }

        if (stripos($contentType, 'application/json') !== false) {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : array();
        }

        $parsed = array();
        parse_str($raw, $parsed);
        if (!empty($parsed)) {
            return $parsed;
        }

        return $_POST;
    }

    public static function data()
    {
        if (self::method() === 'GET') {
            return $_GET;
        }

        return self::body();
    }
}
