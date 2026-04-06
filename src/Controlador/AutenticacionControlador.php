<?php
// src/Controlador/AutenticacionControlador.php

class AutenticacionControlador {
    public function iniciarSesion($datos) {
        $usuario  = trim($datos['usuario'] ?? '');
        $password = trim($datos['password'] ?? '');
        $rol_id   = $datos['rol_id'] ?? '';

        $repositorio = new UsuarioRepositorio();
        $user = $repositorio->buscarPorCredenciales($usuario, $password, $rol_id);

        if ($user) {
            session_start();
            session_regenerate_id(true);
            
            $_SESSION['usuario'] = $user['USUARIO'];
            $_SESSION['clave_promotor'] = $user['CLAVE_ROL'];
            $_SESSION['nombre'] = $user['PMT_NOMBRE'];
        
            $rol = trim($user['ROL']);
            // Mapeo de rutas según el rol de la base de datos
            $rutas = [
                '0' => 'promotores/inicio.php',
                '1' => 'supervisor/inicio.php',
                '2' => 'distribucion/inicio.php'
            ];

            $_SESSION['rol'] = ($rol === '0') ? 'promotor' : (($rol === '1') ? 'supervisor' : 'distribucion');
            
            $destino = $rutas[$rol] ?? 'iniciosesionPromotor.php';
            header("Location: " . $destino);
            exit();
        } else {
            header("Location: iniciosesionPromotor.php?error=1");
            exit();
        }
    }
}