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
    private function existeInventarioMes($lecheria, $fecha)
    {
        if (empty($lecheria) || empty($fecha)) return false;

        $time = strtotime($fecha);
        $mes = date('m', $time);
        $anio = date('Y', $time);
        $lecheria_limpia = str_replace("'", "''", $lecheria);

        $sql = "SELECT ID FROM INVENTARIOS_MENSUALES 
                WHERE CLAVE_LECHERIA = '$lecheria_limpia' 
                AND EXTRACT(YEAR FROM FECHA) = $anio 
                AND EXTRACT(MONTH FROM FECHA) = $mes";

        $stmt = $this->db->query($sql);
        return $stmt->fetch() !== false;
    }
    public function guardar($datos, $usuario)
    {
        if ($this->existeInventarioMes($datos['lecheria'], $datos['fecha'])) {
            $mensaje = "Ya existe un inventario para esta lechería en este mes. " .
                "<a href='editarinventarioMensual.php' style='color: #fff; text-decoration: underline; font-weight: bold;'>Haz clic aquí para ir a editarlo.</a>";
            throw new Exception($mensaje);
        }
        $q = function ($val, $len = 255) {
            if ($val === null || $val === "") return 'NULL';
            $limpio = substr((string)$val, 0, $len);
            return "'" . str_replace("'", "''", $limpio) . "'";
        };
        // 2. Ayudante para limpiar Números
        $n = function ($val) {
            if ($val === null || $val === "") return 0;
            return (int)$val;
        };
        $lecheria_limpia = $datos['lecheria'] ?? 'SIN_CLAVE';
        $time = strtotime($datos['fecha']);
        $anio = date('Y', $time);
        $mes = date('m', $time);
        $pdf_nombre = "Inventario_{$lecheria_limpia}_{$anio}_{$mes}.pdf";

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
            PDF_RUTA, USUARIO, ESTADO, ID
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
            GEN_ID(seq_inventarios_mens_id, 1)
        )";

        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
            }
            $this->db->exec($sql);
            $this->db->commit();
            return ['status' => 'success', 'mensaje' => 'Guardado con éxito'];
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new Exception("Error Firebird: " . $e->getMessage());
        }
    }
    // NUEVA FUNCIÓN: Busca inventarios por lechería y opcionalmente por mes
    // BÚSQUEDA MATA-DINOSAURIOS (Sin parámetros PDO)
    public function buscarPorLecheria($clave, $fecha = '')
    {
        $clave_limpia = str_replace("'", "''", $clave);

        $sql = "SELECT ID, FECHA, MUNICIPIO, COMUNIDAD, FIN_CAJA, FIN_LITROS, ESTADO 
                FROM INVENTARIOS_MENSUALES 
                WHERE CLAVE_LECHERIA = '$clave_limpia'";
        if (!empty($fecha)) {
            $time = strtotime($fecha);
            $mes = (int) date('m', $time);
            $anio = (int) date('Y', $time);
            $sql .= " AND EXTRACT(YEAR FROM FECHA) = $anio AND EXTRACT(MONTH FROM FECHA) = $mes";
        }

        $sql .= " ORDER BY FECHA DESC";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al buscar inventarios: " . $e->getMessage());
        }
    }
    // NUEVA FUNCIÓN: Obtener un solo inventario por su ID
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
