<?php
// src/Servicio/PasswordServicio.php
// Utilidades centralizadas de hashing/verificación de contraseñas (RNF-08).
// Acepta tanto hashes modernos (password_hash) como contraseñas legacy en
// texto plano, permitiendo una migración perezosa sin downtime.

class PasswordServicio
{
    /** Algoritmo por defecto: bcrypt (rápido, soportado por PHP 7.2+). */
    public const ALGO = PASSWORD_BCRYPT;

    /** Hashea una contraseña en texto plano. */
    public static function hashear(string $passPlano): string
    {
        return password_hash($passPlano, self::ALGO);
    }

    /** Determina si un valor almacenado YA es un hash válido reconocible por PHP. */
    public static function esHash(?string $stored): bool
    {
        if ($stored === null || $stored === '') return false;
        $info = password_get_info($stored);
        return !empty($info['algo']);
    }

    /**
     * Verifica una contraseña en texto plano contra el valor almacenado.
     *  - Si el valor es un hash → password_verify().
     *  - Si es texto plano (legacy) → comparación directa.
     * Devuelve true/false.
     */
    public static function verificar(string $passPlano, ?string $stored): bool
    {
        if ($stored === null || $stored === '') return false;
        if (self::esHash($stored)) {
            return password_verify($passPlano, $stored);
        }
        // Legacy: la BDD aún guarda texto plano (compatibilidad con datos
        // sincronizados desde Firebird). Comparación constante para evitar
        // timing attacks.
        return hash_equals(trim($stored), $passPlano);
    }

    /**
     * Indica si conviene rehashear (porque era legacy o el algoritmo cambió).
     * Llamar tras un verificar() exitoso.
     */
    public static function necesitaRehash(?string $stored): bool
    {
        if ($stored === null || $stored === '') return false;
        if (!self::esHash($stored)) return true; // legacy → migrar
        return password_needs_rehash($stored, self::ALGO);
    }
}
