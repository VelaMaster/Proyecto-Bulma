<?php
require_once __DIR__ . '/../Database/DatabaseSQLite.php';

/**
 * LoggerErrores
 * Registra errores (PHP, PDO, sync, app) en SQLite → tabla errores_log
 * y eventos de sync en sync_log. Pensado para mostrarse en /admin/errores.
 */
class LoggerErrores
{
    private static bool $instalado = false;

    /** Engancha set_error_handler + set_exception_handler una sola vez. */
    public static function instalar(): void
    {
        if (self::$instalado) return;
        self::$instalado = true;

        set_error_handler(function ($severidad, $msg, $file, $line) {
            // Respeta @ y error_reporting
            if (!(error_reporting() & $severidad)) return false;
            $mapa = [
                E_ERROR             => 'error',
                E_WARNING           => 'warning',
                E_PARSE             => 'error',
                E_NOTICE            => 'notice',
                E_CORE_ERROR        => 'error',
                E_CORE_WARNING      => 'warning',
                E_COMPILE_ERROR     => 'error',
                E_COMPILE_WARNING   => 'warning',
                E_USER_ERROR        => 'error',
                E_USER_WARNING      => 'warning',
                E_USER_NOTICE       => 'notice',
                E_RECOVERABLE_ERROR => 'error',
                E_DEPRECATED        => 'notice',
                E_USER_DEPRECATED   => 'notice',
            ];
            self::registrar('php', $mapa[$severidad] ?? 'error', $msg, $file, $line);
            return false; // que PHP también lo procese
        });

        set_exception_handler(function (\Throwable $e) {
            $tipo = ($e instanceof \PDOException) ? 'pdo' : 'php';
            self::registrar($tipo, 'error', $e->getMessage(), $e->getFile(), $e->getLine(),
                ['trace' => $e->getTraceAsString()]);
        });

        register_shutdown_function(function () {
            $err = error_get_last();
            if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                self::registrar('php', 'error', $err['message'], $err['file'], $err['line']);
            }
        });
    }

    /** Inserta una fila en errores_log. Captura sus propios fallos (no debe romper la página). */
    public static function registrar(
        string $tipo,
        string $nivel,
        string $mensaje,
        ?string $archivo = null,
        ?int $linea = null,
        array $contexto = []
    ): void {
        try {
            $pdo = DatabaseSQLite::getInstance();
            $usuario = $_SESSION['usuario'] ?? null;
            $pdo->prepare(
                "INSERT INTO errores_log (tipo, nivel, mensaje, archivo, linea, contexto, usuario)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            )->execute([
                $tipo, $nivel, $mensaje, $archivo, $linea,
                $contexto ? json_encode($contexto, JSON_UNESCAPED_UNICODE) : null,
                $usuario,
            ]);
        } catch (\Throwable $e) {
            // último recurso: error_log de PHP
            error_log("[LoggerErrores] " . $e->getMessage() . " — original: $mensaje");
        }
    }

    /** Registra un evento de sincronización Firebird→SQLite. */
    public static function registrarSync(string $tabla, int $filas, int $duracionMs, bool $ok, string $mensaje = ''): void
    {
        try {
            DatabaseSQLite::getInstance()->prepare(
                "INSERT INTO sync_log (tabla, filas, duracion_ms, ok, mensaje) VALUES (?, ?, ?, ?, ?)"
            )->execute([$tabla, $filas, $duracionMs, $ok ? 1 : 0, $mensaje]);
        } catch (\Throwable $e) {
            error_log("[LoggerErrores::sync] " . $e->getMessage());
        }
    }
}
