<?php
// src/Repositories/LecheriaRepository.php
require_once __DIR__ . '/../../Database.php';

class LecheriaRepositorio {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function searchByTerm($term, $usuario_login) {        
        $sql = "SELECT FIRST 20
                    L.LECHER,
                    TRIM(L.NOMBRELECH) as NOMBRELECH,
                    TRIM(M.MUN_DESCRIPCION) as MUNICIPIO_NOMBRE,
                    TRIM(LOC.LOC_DESCRIPCION) as LOCALIDAD_DESC,
                    L.NUM_TIENDA,
                    TRIM(L.ALMACEN_RURAL) as ALMACEN_RURAL,
                    L.CC_FAM as TOTAL_HOGARES,
                    (L.CC_BT1 + L.CC_BT2) as TOTAL_INFANTILES,
                    (L.CC_BT3 + L.CC_BT4 + L.CC_BT5 + L.CC_BT6 + L.CC_BT7) as TOTAL_RESTO
                FROM LECHERIA L
                LEFT JOIN LOCALIDAD LOC ON 
                    (L.EFD_NUMERO = LOC.EFD_NUMERO AND L.MUN_NUMERO = LOC.MUN_NUMERO AND L.LOC_NUMERO = LOC.LOC_NUMERO)
                LEFT JOIN MUNICIPIO M ON
                    (L.EFD_NUMERO = M.EFD_NUMERO AND L.MUN_NUMERO = M.MUN_NUMERO)
                -- Agregamos el JOIN para poder filtrar por el nombre de usuario
                INNER JOIN USUARIOS_INVENTARIOS U ON L.PROMOTOR = U.CLAVE_ROL 
                WHERE L.EFD_NUMERO = 20
                AND U.USUARIO = :usuario "; // Filtramos por el login que traes en $_SESSION['usuario']

        // Si hay texto de búsqueda, agregamos la validación del LIKE
        if ($term !== '') {
            $sql .= " AND (CAST(L.LECHER AS VARCHAR(20)) LIKE :query1 OR UPPER(L.NOMBRELECH) LIKE :query2) ";
        }

        $sql .= " ORDER BY L.NOMBRELECH";

        $stmt = $this->db->prepare($sql);
        
        // Siempre bindeamos al usuario
        $stmt->bindValue(':usuario', $usuario_login, PDO::PARAM_STR);

        // Solo bindeamos query1 y query2 si el término no está vacío
        if ($term !== '') {
            $patron = '%' . strtoupper($term) . '%';
            $stmt->bindValue(':query1', $patron, PDO::PARAM_STR);
            $stmt->bindValue(':query2', $patron, PDO::PARAM_STR);
        }

        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $resultados ?: [];
    }
}