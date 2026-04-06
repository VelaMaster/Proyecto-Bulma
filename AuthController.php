<?php
require_once 'UserRepository.php';
class AuthController {
    public function login($postData) {
        $repo = new UserRepository();
        $user = $repo->findByCredentials(
            trim($postData['usuario'] ?? ''),
            trim($postData['password'] ?? ''),
            $postData['rol_id'] ?? ''
        );
        if ($user) {
            session_start();
            session_regenerate_id(true);
            $_SESSION['usuario'] = $user['USUARIO'];
            $_SESSION['clave_promotor'] = $user['CLAVE_ROL'];
            $_SESSION['nombre'] = $user['PMT_NOMBRE'];
            $roles = ['0' => 'promotores/inicio.php', '1' => 'supervisor/inicio.php', '2' => 'distribucion/inicio.php'];
            $path = $roles[trim($user['ROL'])] ?? 'iniciosesionPromotor.php';
            header("Location: $path");
            exit();
        } else {
            header("Location: iniciosesionPromotor.php?error=1");
            exit();
        }
    }
}