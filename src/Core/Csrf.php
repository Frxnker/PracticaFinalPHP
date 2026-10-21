<?php

namespace App\Core;

class Csrf
{
    /** Devuelve el token de la sesión, creándolo si aún no existe. */
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** Campo oculto listo para meter en un formulario. */
    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="'
            . htmlspecialchars(self::token()) . '">';
    }

    /** Compara el token recibido con el de la sesión. */
    public static function isValid(?string $token): bool
    {
        return is_string($token) && hash_equals(self::token(), $token);
    }
}