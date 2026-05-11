<?php
require_once __DIR__ . '/../../Database.php';

class InventarioRepositorio
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function obtenerHistorialLecheria($lecher)
    {
        $sql = "SELECT VENTA_REAL, INVENTARIO_FINAL 
                FROM INVENTARIO_LEP_SUBSIDIADA 
                WHERE LECHER = :lecher 
                ORDER BY ANIO_PERIODO DESC, MES_PERIODO DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':lecher' => $lecher]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function existeInventarioMes($lecheria, $mes, $anio)
    {
        if (empty($lecheria)) return false;
        $lecheria_limpia = str_replace("'", "''", $lecheria);

        $sql = "SELECT ID FROM INVENTARIOS_MENSUALES 
                WHERE CLAVE_LECHERIA = '$lecheria_limpia' 
                AND ANIO_PERIODO = $anio 
                AND MES_PERIODO = $mes";

        $stmt = $this->db->query($sql);
        return $stmt->fetch() !== false;
    }

    /**
     * Hace UPSERT en INVENTARIO_LEP_SUBSIDIADA.
     * Esta tabla la alimenta originalmente Distribución; ahora también la
     * llenamos cuando un promotor captura su inventario mensual, para que
     * el flujo (reporte → requerimiento del mes+2) se quede sincronizado
     * sin necesidad de cargar datos a mano.
     *
     * Conversión:
     *   INVENTARIO_FINAL    ← fin_litros
     *   SURTIMIENTO         ← surt_cajas
     *   VENTA_REAL          ← venta_litros
     *   VENTA_LIBRO_RETIRO  ← reg_litros
     */
    private function upsertLepSubsidiada($lecher, $mes, $anio, $finLitros, $surtCajas, $ventaLitros, $regLitros)
    {
        if (empty($lecher)) return;

        $lecher_q  = "'" . str_replace("'", "''", $lecher) . "'";
        $mes_n     = (int)$mes;
        $anio_n    = (int)$anio;
        $finL      = (int)$finLitros;
        $surtC     = (int)$surtCajas;
        $ventaL    = (int)$ventaLitros;
        $regL      = (int)$regLitros;

        // Intentamos UPDATE primero; si no afectó filas, INSERT.
        $sqlUpd = "UPDATE INVENTARIO_LEP_SUBSIDIADA SET
                       INVENTARIO_FINAL   = $finL,
                       SURTIMIENTO        = $surtC,
                       VENTA_REAL         = $ventaL,
                       VENTA_LIBRO_RETIRO = $regL
                   WHERE LECHER = $lecher_q
                     AND MES_PERIODO  = $mes_n
                     AND ANIO_PERIODO = $anio_n";
        $afectadas = $this->db->exec($sqlUpd);

        if ($afectadas === 0 || $afectadas === false) {
            $sqlIns = "INSERT INTO INVENTARIO_LEP_SUBSIDIADA
                       (LECHER, MES_PERIODO, ANIO_PERIODO,
                        INVENTARIO_FINAL, SURTIMIENTO, VENTA_REAL, VENTA_LIBRO_RETIRO)
                       VALUES ($lecher_q, $mes_n, $anio_n, $finL, $surtC, $ventaL, $regL)";
            try {
                $this->db->exec($sqlIns);
            } catch (PDOException $e) {
                // Si por carrera otro proceso ya insertó, reintentamos UPDATE.
                $this->db->exec($sqlUpd);
            }
        }
    }

    public function guardar($datos, $usuario)
    {
        $lecheria_limpia = $datos['lecheria'] ?? 'SIN_CLAVE';

        $anio_actual = !empty($datos['anio_periodo']) ? (int)$datos['anio_periodo'] : (int)date('Y', strtotime($datos['fecha']));
        $mes_actual  = !empty($datos['mes_periodo'])  ? (int)$datos['mes_periodo']  : (int)date('m', strtotime($datos['fecha']));

        // Bloquear duplicados — el JS ya debería haber detectado esto antes de llegar aquí,
        // pero lo dejamos como red de seguridad en el backend
        if ($this->existeInventarioMes($lecheria_limpia, $mes_actual, $anio_actual)) {
            throw new Exception("Ya existe un inventario para esta lechería en este periodo. Recarga la página para activar el modo edición.");
        }

        // Verificar mes anterior
        $mes_anterior  = $mes_actual - 1;
        $anio_anterior = $anio_actual;
        if ($mes_anterior <= 0) {
            $mes_anterior  = 12;
            $anio_anterior = $anio_actual - 1;
        }

        if (!$this->existeInventarioMes($lecheria_limpia, $mes_anterior, $anio_anterior) && empty($datos['confirmado_periodo'])) {
            $nombres_meses = ["", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
                              "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
            $nombre_mes_ant = $nombres_meses[$mes_anterior] ?? '';
            
            return [
                'status'  => 'requiere_confirmacion',
                'mensaje' => "Oye, te falta registrar el inventario de $nombre_mes_ant del $anio_anterior.\n\n¿Estás seguro de que quieres guardar este mes aunque falte el anterior?"
            ];
        }

        $q = function ($val, $len = 255) {
            if ($val === null || $val === "") return 'NULL';
            $limpio = substr((string)$val, 0, $len);
            return "'" . str_replace("'", "''", $limpio) . "'";
        };
        $n = function ($val) {
            if ($val === null || $val === "") return 0;
            return (int)$val;
        };

        $pdf_nombre = "Inventario_{$lecheria_limpia}_{$anio_actual}_{$mes_actual}.pdf";

        $sql = "INSERT INTO INVENTARIOS_MENSUALES (
            FECHA, CLAVE_LECHERIA, CLAVE_TIENDA, ALMACEN, MUNICIPIO, COMUNIDAD,
            SURT_FECHA, SURT_CAJAS, SURT_LITROS, SURT_FACTURA, SURT_CADUCIDAD,
            INV_INI_CAJA, INV_INI_SOBRES, INV_INI_LITROS,
            ABASTO_CAJA, ABASTO_SOBRES, ABASTO_LITROS,
            VENTA_CAJA, VENTA_SOBRES, VENTA_LITROS,
            REG_CAJA, REG_SOBRES, REG_LITROS,
            DIF_CAJA, DIF_SOBRES, DIF_LITROS,
            FIN_CAJA, FIN_SOBRES, FIN_LITROS,
            HOGARES, MENORES, MAYORES, DOTACION,
            PDF_RUTA, USUARIO, ESTADO, MES_PERIODO, ANIO_PERIODO, ID
        ) VALUES (
            " . $q($datos['fecha'], 10) . ", 
            " . $q($datos['lecheria'], 20) . ", 
            " . $q($datos['tienda'], 20) . ", 
            " . $q($datos['almacen'], 100) . ", 
            " . $q($datos['municipio'], 100) . ", 
            " . $q($datos['comunidad'], 100) . ", 
            " . $q($datos['surt_fecha'], 10) . ", 
            " . $n($datos['surt_cajas']) . ", 
            " . $n($datos['surt_litros']) . ", 
            " . $q($datos['surt_factura'], 60) . ", 
            " . $q($datos['surt_caducidad'], 10) . ", 
            " . $n($datos['inv_ini_caja']) . ", " . $n($datos['inv_ini_sobres']) . ", " . $n($datos['inv_ini_litros']) . ",
            " . $n($datos['abasto_caja']) . ", " . $n($datos['abasto_sobres']) . ", " . $n($datos['abasto_litros']) . ",
            " . $n($datos['venta_caja']) . ", " . $n($datos['venta_sobres']) . ", " . $n($datos['venta_litros']) . ",
            " . $n($datos['reg_caja']) . ", " . $n($datos['reg_sobres']) . ", " . $n($datos['reg_litros']) . ",
            " . $n($datos['dif_caja']) . ", " . $n($datos['dif_sobres']) . ", " . $n($datos['dif_litros']) . ",
            " . $n($datos['fin_caja']) . ", " . $n($datos['fin_sobres']) . ", " . $n($datos['fin_litros']) . ",
            " . $n($datos['hogares']) . ", " . $n($datos['menores']) . ", " . $n($datos['mayores']) . ", " . $n($datos['dotacion']) . ",
            " . $q($pdf_nombre, 255) . ", 
            " . $q($usuario, 100) . ", 
            'guardado', 
            $mes_actual, 
            $anio_actual,
            GEN_ID(seq_inventarios_mens_id, 1)
        )";

        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
            }
            $this->db->exec($sql);

            // Sincroniza con la tabla "oficial" de Distribución
            $this->upsertLepSubsidiada(
                $lecheria_limpia,
                $mes_actual,
                $anio_actual,
                $datos['fin_litros']   ?? 0,
                $datos['surt_cajas']   ?? 0,
                $datos['venta_litros'] ?? 0,
                $datos['reg_litros']   ?? 0
            );

            $this->db->commit();
            return ['status' => 'success', 'mensaje' => 'Guardado con éxito'];
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new Exception("Error Firebird: " . $e->getMessage());
        }
    }

    /**
     * Llamado desde actualizar_inventario.php tras hacer el UPDATE en
     * INVENTARIOS_MENSUALES. Mantiene INVENTARIO_LEP_SUBSIDIADA sincronizada.
     */
    public function syncLepSubsidiada($lecher, $mes, $anio, $datos)
    {
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }
        try {
            $this->upsertLepSubsidiada(
                $lecher,
                $mes,
                $anio,
                $datos['fin_litros']   ?? 0,
                $datos['surt_cajas']   ?? 0,
                $datos['venta_litros'] ?? 0,
                $datos['reg_litros']   ?? 0
            );
            $this->db->commit();
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function buscarPorLecheria($clave, $mes = '', $anio = '')
    {
        $clave_limpia = str_replace("'", "''", $clave);

        $sql = "SELECT ID, FECHA, MUNICIPIO, COMUNIDAD, FIN_CAJA, FIN_LITROS, ESTADO 
                FROM INVENTARIOS_MENSUALES 
                WHERE CLAVE_LECHERIA = '$clave_limpia'";
                
        if (!empty($mes) && !empty($anio)) {
            $sql .= " AND ANIO_PERIODO = " . (int)$anio . " AND MES_PERIODO = " . (int)$mes;
        }

        $sql .= " ORDER BY ANIO_PERIODO DESC, MES_PERIODO DESC";
        
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al buscar inventarios: " . $e->getMessage());
        }
    }

    public function obtenerPorId($id)
    {
        $id_limpio = (int)$id;
        $sql = "SELECT * FROM INVENTARIOS_MENSUALES WHERE ID = $id_limpio";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al buscar el inventario por ID: " . $e->getMessage());
        }
    }
}