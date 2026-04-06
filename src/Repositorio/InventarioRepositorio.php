<?php
// src/Repositorio/InventarioRepositorio.php
require_once __DIR__ . '/../../Database.php';

class InventarioRepositorio
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        // Es vital que EMULATE_PREPARES sea false para que Firebird reciba los tipos de datos reales
        $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * MÉTODOS DE LIMPIEZA (Sanitización para Firebird)
     */
    private function toInt($val)
    {
        return (empty($val) && $val !== '0' && $val !== 0) ? 0 : (int)$val;
    }

    private function toDate($val)
    {
        return (empty($val) || $val === "") ? null : $val;
    }

    private function toString($val)
    {
        return (empty($val) && $val !== '0') ? null : (string)$val;
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

    // ... (buscarPorLecheria y obtenerPorId se mantienen igual) ...

    public function guardar($datos, $usuario)
    {
        $f = function ($key) use ($datos) {
            return (!isset($datos[$key]) || $datos[$key] === "" || $datos[$key] === null)
                ? null
                : $datos[$key];
        };

        $i = function ($key) use ($datos) {
            return (!isset($datos[$key]) || $datos[$key] === "" || $datos[$key] === null)
                ? 0
                : (int)$datos[$key];
        };

        // ← CAMBIO CLAVE: null en lugar de string vacío
        $s = function ($key, $len) use ($datos) {
            $val = $datos[$key] ?? null;
            if ($val === "" || $val === null) return null;
            return substr((string)$val, 0, $len);
        };

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
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, 'guardado', NEXT VALUE FOR seq_inventarios_mens_id
            )";

        try {
            if (!$this->db->inTransaction()) $this->db->beginTransaction();
            $stmt = $this->db->prepare($sql);

            $valores = [
                $f('fecha'),            // 1  FECHA          ← NOT NULL, viene '2026-04-06' ✅
                $s('lecheria', 20),     // 2  CLAVE_LECHERIA ← NOT NULL
                $s('tienda', 20),       // 3  CLAVE_TIENDA
                $s('almacen', 100),     // 4  ALMACEN
                $s('municipio', 100),   // 5  MUNICIPIO
                $s('comunidad', 100),   // 6  COMUNIDAD

                $f('surt_fecha'),       // 7  SURT_FECHA     ← nullable DATE
                $i('surt_cajas'),       // 8  SURT_CAJAS
                $i('surt_litros'),      // 9  SURT_LITROS
                $s('surt_factura', 60), // 10 SURT_FACTURA   ← viene "" → ahora NULL ✅
                $f('surt_caducidad'),   // 11 SURT_CADUCIDAD ← viene "" → NULL ✅

                $i('inv_ini_caja'),     // 12
                $i('inv_ini_sobres'),   // 13
                $i('inv_ini_litros'),   // 14

                $i('abasto_caja'),      // 15
                $i('abasto_sobres'),    // 16
                $i('abasto_litros'),    // 17

                $i('venta_caja'),       // 18 ← viene "" → 0 ✅
                $i('venta_sobres'),     // 19
                $i('venta_litros'),     // 20

                $i('reg_caja'),         // 21 ← viene "" → 0 ✅
                $i('reg_sobres'),       // 22
                $i('reg_litros'),       // 23

                $i('dif_caja'),         // 24 ← viene "" → 0 ✅
                $i('dif_sobres'),       // 25
                $i('dif_litros'),       // 26

                $i('fin_caja'),         // 27
                $i('fin_sobres'),       // 28
                $i('fin_litros'),       // 29

                $i('hogares'),          // 30
                $i('menores'),          // 31
                $i('mayores'),          // 32
                $i('dotacion'),         // 33

                // PDF_RUTA — usando lecheria que viene como string limpio
                substr("Inventario_" . ($datos['lecheria'] ?? 'SIN_CLAVE') . ".pdf", 0, 255), // 34
                $s('usuario', 100),     // 35 ← el $usuario del parámetro, no de $datos
            ];

            // ← IMPORTANTE: bindValue explícito para forzar tipos con Firebird+NONE
            foreach ($valores as $idx => $val) {
                $pos = $idx + 1;
                if (is_null($val)) {
                    $stmt->bindValue($pos, null, PDO::PARAM_NULL);
                } elseif (is_int($val)) {
                    $stmt->bindValue($pos, $val, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($pos, $val, PDO::PARAM_STR);
                }
            }

            $stmt->execute();
            $this->db->commit();

            return ['status' => 'success', 'mensaje' => 'Guardado con éxito'];
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw new Exception("Error Firebird: " . $e->getMessage());
        }
    }
}
