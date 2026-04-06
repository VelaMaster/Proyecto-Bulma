<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/src/Controlador/AutenticacionControlador.php';
require_once __DIR__ . '/src/Repositorio/UsuarioRepositorio.php';

$auth = new AutenticacionControlador();
$auth->iniciarSesion($_POST);