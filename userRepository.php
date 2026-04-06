<?php
require_once 'Database.php';
class UserRepository {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance();
    }
    public function findByCredentials($usuario, $password, $rol) {
        $sql = "SELECT U.USUARIO, U.ROL, U.CLAVE_ROL, P.PMT_NOMBRE 
                FROM USUARIOS_INVENTARIOS U
                LEFT JOIN PROMOTOR P ON U.CLAVE_ROL = P.PMT_NUMERO
                WHERE U.USUARIO = :usuario 
                AND U.CONTRASENA = :pass 
                AND U.ROL = :rol";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':usuario' => $usuario,
            ':pass'    => $password,
            ':rol'     => $rol
        ]);
        return $stmt->fetch();
    }
}