<?php
// src/Controlador/AutenticacionControlador.php

require_once __DIR__ . '/../../src/Servicio/RecuerdameServicio.php';

class AutenticacionControlador {
    public function iniciarSesion($datos) {
        $usuario   = trim($datos['usuario']  ?? '');
        $password  = trim($datos['password'] ?? '');
        $rol_id    = $datos['rol_id']        ?? '';
        $recordar  = !empty($datos['recordar_sesion']); // checkbox

        $repositorio = new UsuarioRepositorio();
        $user = $repositorio->buscarPorCredenciales($usuario, $password, $rol_id);

        if ($user) {
            // Configurar duración de sesión ANTES de session_start
            $lifetime = $recordar
                ? (RecuerdameServicio::DIAS_EXPIRY * 86400) // 30 días
                : (8 * 3600);                               // 8 horas

            $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Strict',
                'secure'   => $secure,
            ]);
            ini_set('session.gc_maxlifetime', $lifetime);

            session_start();
            session_regenerate_id(true);

            $_SESSION['usuario']        = $user['USUARIO'];
            $_SESSION['clave_rol']      = $user['CLAVE_ROL'];
            $_SESSION['clave_promotor'] = $user['CLAVE_ROL'];
            $_SESSION['nombre']         = $user['NOMBRE_MOSTRAR'];

            $rol = trim($user['ROL']);
            $_SESSION['rol'] = ($rol === '0') ? 'promotor' : (($rol === '1') ? 'supervisor' : 'distribucion');

            // Crear token remember-me persistente si el usuario lo pidió
            if ($recordar) {
                RecuerdameServicio::crear([
                    'usuario'   => $user['USUARIO'],
                    'nombre'    => $user['NOMBRE_MOSTRAR'],
                    'rol'       => $_SESSION['rol'],
                    'clave_rol' => $user['CLAVE_ROL'],
                ]);
            }

            $rutas = [
                '0' => 'promotores/inicio.php',
                '1' => 'supervisor/inicio.php',
                '2' => 'distribucion/inicio.php',
            ];
            $destino = $rutas[$rol] ?? 'iniciosesionPromotor.php';
            header("Location: " . $destino);
            exit();
        } else {
            header("Location: iniciosesionPromotor.php?error=1");
            exit();
        }
    }
}