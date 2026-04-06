<?php
// src/Repositorio/UsuarioRepositorio.php

class UsuarioRepositorio {
    private $db;

    public function __construct() {
        // Database.php está en la raíz, por eso subimos dos niveles
        require_once __DIR__ . '/../../Database.php';
        $this->db = Database::getInstance();
    }

    public function buscarPorCredenciales($usuario, $pass, $rol) {
        $sql = "SELECT U.USUARIO, U.ROL, U.CLAVE_ROL, P.PMT_NOMBRE 
                FROM USUARIOS_INVENTARIOS U
                LEFT JOIN PROMOTOR P ON U.CLAVE_ROL = P.PMT_NUMERO
                WHERE U.USUARIO = :usuario 
                AND U.CONTRASENA = :pass 
                AND U.ROL = :rol";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':usuario' => $usuario,
                ':pass'    => $pass,
                ':rol'     => $rol
            ]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error en UsuarioRepositorio: " . $e->getMessage());
            return false;
        }
    }
}