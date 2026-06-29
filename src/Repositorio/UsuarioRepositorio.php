<?php
// src/Repositorio/UsuarioRepositorio.php
// MIGRADO a SQLite (Fase 3). Consultas reescritas para sintaxis SQLite.
require_once __DIR__ . '/../Database/DatabaseSQLite.php';
require_once __DIR__ . '/../Servicio/PasswordServicio.php';

class UsuarioRepositorio
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseSQLite::getInstance();
    }

    /**
     * Busca un usuario por credenciales y rol.
     * Combina con PROMOTOR / SUPERVISOR para resolver el nombre a mostrar.
     */
    public function buscarPorCredenciales(string $usuario, string $pass, string $rol)
    {
        // ROL en usuarios_inventarios viene tal cual de Firebird: '0' promotor, '1' supervisor, '2' distribución
        // Se filtra por usuario+rol y se verifica el password en PHP (acepta hash y legacy texto plano).
        $sql = "SELECT U.USUARIO,
                       U.CONTRASENA,
                       U.ROL,
                       U.CLAVE_ROL,
                       COALESCE(P.PMT_NOMBRE, S.NOMBRE_SUPERVISOR, U.NOMBRE) AS NOMBRE_MOSTRAR
                FROM usuarios_inventarios U
                LEFT JOIN promotor   P ON U.CLAVE_ROL = P.PMT_NUMERO   AND U.ROL = '0'
                LEFT JOIN supervisor S ON U.CLAVE_ROL = S.ID_SUPERVISOR AND U.ROL = '1'
                WHERE U.USUARIO    = :usuario
                  AND U.ROL        = :rol
                  AND COALESCE(U.ACTIVO,1) = 1";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':usuario' => $usuario, ':rol' => $rol]);
            $row = $stmt->fetch();
            if (!$row) return false;

            if (!PasswordServicio::verificar($pass, $row['CONTRASENA'])) {
                return false;
            }

            // Migración perezosa: si la contraseña estaba en texto plano o necesita rehash, la actualizamos.
            if (PasswordServicio::necesitaRehash($row['CONTRASENA'])) {
                try {
                    $nuevo = PasswordServicio::hashear($pass);
                    $upd = $this->db->prepare("UPDATE usuarios_inventarios SET CONTRASENA = :h WHERE USUARIO = :u");
                    $upd->execute([':h' => $nuevo, ':u' => $row['USUARIO']]);
                } catch (\Throwable $e) {
                    // No bloquea el login si falla el rehash; solo se registra.
                    error_log("Rehash falló para {$row['USUARIO']}: " . $e->getMessage());
                }
            }

            unset($row['CONTRASENA']); // no propagar el hash al resto del sistema
            return $row;
        } catch (PDOException $e) {
            error_log("Error en buscarPorCredenciales: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lista promotores asignados a un supervisor, con sus lecherías activas.
     * EN_OPERACION = 0 → lechería activa.
     */
    public function obtenerPromotoresDeSupervisor(int $id_supervisor)
    {
        $sql = "
            SELECT P.PMT_NUMERO,
                   P.PMT_NOMBRE,
                   L.LECHER     AS NUMERO_LECHERIA,
                   L.NOMBRELECH AS NOMBRE_LECHERIA
            FROM promotor P
            JOIN lecheria L ON L.PROMOTOR = P.PMT_NUMERO
            WHERE EXISTS (
                    SELECT 1
                    FROM mapeo_supervisor_lecheria M
                    JOIN lecheria L2 ON M.LECHER = L2.LECHER
                    WHERE M.ID_SUPERVISOR = :id_supervisor
                      AND L2.PROMOTOR    = P.PMT_NUMERO
                  )
              AND P.PMT_ACTIVO = 'S'
              AND COALESCE(L.EN_OPERACION, 0) = 0
            ORDER BY P.PMT_NOMBRE, L.LECHER
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_supervisor' => $id_supervisor]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $promotores = [];
            foreach ($rows as $f) {
                $id = $f['PMT_NUMERO'];
                if (!isset($promotores[$id])) {
                    $promotores[$id] = [
                        'id' => $id,
                        'nombre' => trim($f['PMT_NOMBRE'] ?? ''),
                        'cantidad_lecherias' => 0,
                        'lecherias' => [],
                    ];
                }
                $promotores[$id]['lecherias'][] = [
                    'numero' => $f['NUMERO_LECHERIA'],
                    'nombre' => trim($f['NOMBRE_LECHERIA'] ?? 'Sin descripción'),
                ];
                $promotores[$id]['cantidad_lecherias']++;
            }
            return array_values($promotores);
        } catch (PDOException $e) {
            error_log("Error en obtenerPromotoresDeSupervisor: " . $e->getMessage());
            return false;
        }
    }
}
