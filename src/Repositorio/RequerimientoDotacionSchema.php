<?php
// ────────────────────────────────────────────────────────────────────
//  Auto-creación idempotente de la tabla REQUERIMIENTO_DOTACION.
//  Llama a asegurarTabla($pdo) antes de leer/escribir; si la tabla ya
//  existe no hace nada, si no existe la crea.
// ────────────────────────────────────────────────────────────────────

class RequerimientoDotacionSchema
{
    /**
     * Firebird+PDO no tiene autocommit por default: cualquier query
     * (incluso un SELECT al diccionario del sistema o un DDL) abre una
     * transacción implícita que se queda viva hasta un commit/rollback.
     * Si después se intenta hacer beginTransaction(), PHP arroja
     * "There is already an active transaction".
     *
     * Esta función cierra cualquier transacción que pueda haber quedado
     * abierta, así el caller puede iniciar la suya sin choques.
     */
    private static function cerrarTxPendiente(PDO $pdo): void
    {
        if ($pdo->inTransaction()) {
            try { $pdo->commit(); } catch (Exception $e) { /* ignoramos */ }
        }
    }

    public static function existe(PDO $pdo): bool
    {
        $sql = "SELECT 1
                FROM RDB\$RELATIONS
                WHERE RDB\$RELATION_NAME = 'REQUERIMIENTO_DOTACION'
                  AND RDB\$SYSTEM_FLAG = 0";
        try {
            $stmt = $pdo->query($sql);
            $r = (bool)$stmt->fetchColumn();
            $stmt->closeCursor();
            return $r;
        } catch (Exception $e) {
            return false;
        } finally {
            // El SELECT al diccionario abre tx implícita; ciérrala.
            self::cerrarTxPendiente($pdo);
        }
    }

    public static function asegurarTabla(PDO $pdo): void
    {
        try {
            if (self::existe($pdo)) {
                return;
            }

            // Firebird no soporta CREATE TABLE IF NOT EXISTS; intentamos crear
            // y si falla por "objeto ya existe" lo absorbemos.
            $ddl = "
                CREATE TABLE REQUERIMIENTO_DOTACION (
                    CLAVE_LECHERIA    VARCHAR(20) NOT NULL,
                    MES_BASE          INTEGER     NOT NULL,
                    ANIO_BASE         INTEGER     NOT NULL,
                    PROMOTOR          INTEGER,
                    MES_DESTINO       INTEGER,
                    ANIO_DESTINO      INTEGER,
                    FAMILIAS          INTEGER     DEFAULT 0,
                    BENEFICIARIOS     INTEGER     DEFAULT 0,
                    DOTACION_TEORICA  INTEGER     DEFAULT 0,
                    INV_INICIAL       VARCHAR(30),
                    SURTIMIENTO       INTEGER     DEFAULT 0,
                    VENTAS            VARCHAR(30),
                    INV_FINAL         VARCHAR(30),
                    REQ_MS_ANTERIOR   INTEGER     DEFAULT 0,
                    VMS               INTEGER     DEFAULT 0,
                    REQ_ACTUAL        INTEGER     DEFAULT 0,
                    OBSERVACIONES     VARCHAR(500),
                    FECHA_CAPTURA     TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
                    USUARIO_CAPTURA   VARCHAR(50),
                    CONSTRAINT PK_REQ_DOT PRIMARY KEY (CLAVE_LECHERIA, MES_BASE, ANIO_BASE)
                )";

            try {
                $pdo->exec($ddl);
            } catch (PDOException $e) {
                $msg = $e->getMessage();
                if (stripos($msg, 'already exists') === false &&
                    stripos($msg, 'unsuccessful metadata update') === false) {
                    throw $e;
                }
            }

            // Índices auxiliares.
            $indices = [
                "CREATE INDEX IDX_REQ_DOT_PROMOTOR ON REQUERIMIENTO_DOTACION (PROMOTOR)",
                "CREATE INDEX IDX_REQ_DOT_PERIODO  ON REQUERIMIENTO_DOTACION (MES_BASE, ANIO_BASE)",
            ];
            foreach ($indices as $idx) {
                try { $pdo->exec($idx); } catch (Exception $e) { /* ignoramos */ }
            }
        } finally {
            // Cualquier DDL/SELECT anterior deja tx implícita: la cerramos
            // para que el caller pueda iniciar su propia transacción limpia.
            self::cerrarTxPendiente($pdo);
        }
    }
}
