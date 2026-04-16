<?php
// src/Repositorio/UsuarioRepositorio.php

class UsuarioRepositorio {
    private $db;

    public function __construct() {
        // Database.php está en la raíz, por eso subimos dos niveles
        require_once __DIR__ . '/../../Database.php';
        $this->db = Database::getInstance();
    }

// src/Repositorio/UsuarioRepositorio.php

public function buscarPorCredenciales($usuario, $pass, $rol) {
    // Usamos COALESCE para traer el nombre de la tabla que tenga datos
    $sql = "SELECT U.USUARIO, U.ROL, U.CLAVE_ROL, 
                   COALESCE(P.PMT_NOMBRE, S.NOMBRE_SUPERVISOR) AS NOMBRE_MOSTRAR 
            FROM USUARIOS_INVENTARIOS U
            LEFT JOIN PROMOTOR P ON (U.CLAVE_ROL = P.PMT_NUMERO AND U.ROL = '0')
            LEFT JOIN SUPERVISOR S ON (U.CLAVE_ROL = S.ID_SUPERVISOR AND U.ROL = '1')
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