<?php
// src/Repositorio/LecheriaRepositorio.php
// MIGRADO a SQLite (Fase 3.2). FIRST n → LIMIT n. CAST AS VARCHAR(n) → CAST AS TEXT.
require_once __DIR__ . '/../Database/DatabaseSQLite.php';

class LecheriaRepositorio
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseSQLite::getInstance();
    }

    /**
     * Busca lecherías asignadas al promotor por número o nombre.
     * Máximo 20 resultados. Si $term está vacío devuelve las primeras 20 ordenadas por nombre.
     */
    public function searchByTerm(string $term, string $usuario_login): array
    {
        $sql = "SELECT
                    L.LECHER,
                    TRIM(L.NOMBRELECH)        AS NOMBRELECH,
                    TRIM(M.MUN_DESCRIPCION)   AS MUNICIPIO_NOMBRE,
                    TRIM(LOC.LOC_DESCRIPCION) AS LOCALIDAD_DESC,
                    L.NUM_TIENDA,
                    L.TIPO_PUNTO_VENTA,
                    TRIM(L.ALMACEN_RURAL)     AS ALMACEN_RURAL,
                    L.CC_FAM                  AS TOTAL_HOGARES,
                    (L.CC_BT1 + L.CC_BT2)     AS TOTAL_INFANTILES,
                    (L.CC_BT3 + L.CC_BT4 + L.CC_BT5 + L.CC_BT6 + L.CC_BT7) AS TOTAL_RESTO
                FROM lecheria L
                LEFT JOIN localidad LOC
                       ON L.EFD_NUMERO = LOC.EFD_NUMERO
                      AND L.MUN_NUMERO = LOC.MUN_NUMERO
                      AND L.LOC_NUMERO = LOC.LOC_NUMERO
                LEFT JOIN municipio M
                       ON L.EFD_NUMERO = M.EFD_NUMERO
                      AND L.MUN_NUMERO = M.MUN_NUMERO
                INNER JOIN usuarios_inventarios U
                       ON L.PROMOTOR = U.CLAVE_ROL
                WHERE L.EFD_NUMERO = 20
                  AND U.USUARIO    = :usuario ";

        if ($term !== '') {
            $sql .= " AND (CAST(L.LECHER AS TEXT) LIKE :query1
                       OR UPPER(L.NOMBRELECH)    LIKE :query2) ";
        }
        $sql .= " ORDER BY L.NOMBRELECH LIMIT 20";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario', $usuario_login, PDO::PARAM_STR);

        if ($term !== '') {
            $patron = '%' . strtoupper($term) . '%';
            $stmt->bindValue(':query1', $patron, PDO::PARAM_STR);
            $stmt->bindValue(':query2', $patron, PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
