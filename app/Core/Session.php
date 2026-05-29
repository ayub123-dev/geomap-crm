<?php

namespace App\Core;

class Session
{
    public static function start()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function set($key, $value)
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get($key, $default = null)
    {
        self::start();
        return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;
    }

    public static function forget($key)
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function regenerate()
    {
        self::start();
        session_regenerate_id(true);
    }
}
