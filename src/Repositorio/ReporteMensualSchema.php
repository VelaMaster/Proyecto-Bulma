<?php
// ────────────────────────────────────────────────────────────────────
//  Auto-creación idempotente de la tabla REPORTE_MENSUAL_LECHER.
//  Una fila por lechería × mes × año.  PK: (CLAVE_LECHERIA, MES, ANIO)
//
//  Llama asegurarTabla($pdo) antes de leer/escribir; si la tabla ya
//  existe no hace nada.
// ────────────────────────────────────────────────────────────────────

class ReporteMensualSchema
{
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
                WHERE RDB\$RELATION_NAME = 'REPORTE_MENSUAL_LECHER'
                  AND RDB\$SYSTEM_FLAG = 0";
        try {
            $stmt = $pdo->query($sql);
            $r = (bool)$stmt->fetchColumn();
            $stmt->closeCursor();
            return $r;
        } catch (Exception $e) {
            return false;
        } finally {
            self::cerrarTxPendiente($pdo);
        }
    }

    public static function asegurarTabla(PDO $pdo): void
    {
        try {
            if (self::existe($pdo)) return;

            $ddl = "
                CREATE TABLE REPORTE_MENSUAL_LECHER (
                    CLAVE_LECHERIA    VARCHAR(20)   NOT NULL,
                    MES               INTEGER       NOT NULL,
                    ANIO              INTEGER       NOT NULL,
                    ALMACEN           VARCHAR(50),
                    PRECIO            VARCHAR(10),
                    INV_INI_CAJAS     INTEGER       DEFAULT 0,
                    INV_INI_SOBRES    INTEGER       DEFAULT 0,
                    DOT_RECIB_CAJAS   INTEGER       DEFAULT 0,
                    TOTAL_CAJAS       INTEGER       DEFAULT 0,
                    TOTAL_SOBRES      INTEGER       DEFAULT 0,
                    VEND_CAJAS        INTEGER       DEFAULT 0,
                    VEND_SOBRES       INTEGER       DEFAULT 0,
                    INV_FIN_CAJAS     INTEGER       DEFAULT 0,
                    INV_FIN_SOBRES    INTEGER       DEFAULT 0,
                    RETIRO_CAJAS      INTEGER       DEFAULT 0,
                    RETIRO_SOBRES     INTEGER       DEFAULT 0,
                    FAMILIAS_NO_ACUD  INTEGER       DEFAULT 0,
                    SOBRES_ROTOS      INTEGER       DEFAULT 0,
                    SOBRES_FALT       INTEGER       DEFAULT 0,
                    OBSERVACIONES     VARCHAR(500),
                    PERIODO_INICIO    VARCHAR(20),
                    PERIODO_FIN       VARCHAR(20),
                    PROMOTOR          VARCHAR(100),
                    SUPERVISOR        VARCHAR(100),
                    USUARIO_CAPTURA   VARCHAR(50),
                    FECHA_CAPTURA     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT PK_RPT_MENSUAL PRIMARY KEY (CLAVE_LECHERIA, MES, ANIO)
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

            $indices = [
                "CREATE INDEX IDX_RPT_MENSUAL_MES ON REPORTE_MENSUAL_LECHER (MES, ANIO)",
                "CREATE INDEX IDX_RPT_MENSUAL_USR ON REPORTE_MENSUAL_LECHER (USUARIO_CAPTURA)",
            ];
            foreach ($indices as $idx) {
                try { $pdo->exec($idx); } catch (Exception $e) { /* ignoramos */ }
            }
        } finally {
            self::cerrarTxPendiente($pdo);
        }
    }
}
