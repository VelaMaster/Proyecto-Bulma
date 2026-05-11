<?php
// src/Repositorio/UsuarioRepositorio.php

class UsuarioRepositorio
{
    private $db;

    public function __construct()
    {
        // Database.php está en la raíz, por eso subimos dos niveles
        require_once __DIR__ . '/../../Database.php';
        $this->db = Database::getInstance();
    }

    public function buscarPorCredenciales($usuario, $pass, $rol)
    {
        // Agregamos U.NOMBRE al final del COALESCE
        $sql = "SELECT U.USUARIO, U.ROL, U.CLAVE_ROL, 
                   COALESCE(P.PMT_NOMBRE, S.NOMBRE_SUPERVISOR, U.NOMBRE) AS NOMBRE_MOSTRAR 
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
            error_log("Error en buscarPorCredenciales: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerPromotoresDeSupervisor($id_supervisor)
    {
        $sql = "
            SELECT 
                P.PMT_NUMERO, 
                P.PMT_NOMBRE, 
                L.LECHER AS NUMERO_LECHERIA, 
                L.NOMBRELECH AS NOMBRE_LECHERIA
            FROM MAPEO_SUPERVISOR_LECHERIA M
            JOIN LECHERIA L ON M.LECHER = L.LECHER
            JOIN PROMOTOR P ON L.PROMOTOR = P.PMT_NUMERO
            WHERE M.ID_SUPERVISOR = :id_supervisor
              AND P.PMT_ACTIVO = 'S'
              AND COALESCE(L.EN_OPERACION, 0) = 0   -- 0 = activa, 1 = baja
            ORDER BY P.PMT_NOMBRE, L.LECHER
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_supervisor' => $id_supervisor]);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $promotores = [];

            // Agrupamos la data
            foreach ($resultados as $fila) {
                $id_promotor = $fila['PMT_NUMERO'];
                
                if (!isset($promotores[$id_promotor])) {
                    $promotores[$id_promotor] = [
                        'id' => $id_promotor,
                        'nombre' => trim($fila['PMT_NOMBRE']),
                        'cantidad_lecherias' => 0,
                        'lecherias' => []
                    ];
                }
                
                $promotores[$id_promotor]['lecherias'][] = [
                    'numero' => $fila['NUMERO_LECHERIA'],
                    'nombre' => trim($fila['NOMBRE_LECHERIA'] ?? 'Sin descripción')
                ];
                
                $promotores[$id_promotor]['cantidad_lecherias']++;
            }

            // Devolvemos un array indexado numéricamente
            return array_values($promotores);

        } catch (PDOException $e) {
            error_log("Error en obtenerPromotoresDeSupervisor: " . $e->getMessage());
            return false; // Retornamos false si hay un error en BD
        }
    }
}