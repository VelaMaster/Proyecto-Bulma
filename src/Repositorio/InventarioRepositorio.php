<?php
// src/Repositorio/InventarioRepositorio.php
// MIGRADO a SQLite (Fase 3.2).
// - FIRST n → LIMIT n
// - EXTRACT(YEAR/MONTH FROM FECHA) → strftime('%Y'/'%m', FECHA)
// - GEN_ID(seq_…) → NULL (AUTOINCREMENT en SQLite)
// - INVENTARIO_LEP_SUBSIDIADA ya no se usa: el código que la sincronizaba
//   queda como no-op porque INVENTARIOS_MENSUALES es la única fuente de verdad.
require_once __DIR__ . '/../Database/DatabaseSQLite.php';

class InventarioRepositorio
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseSQLite::getInstance();
    }

    /**
     * Genera lista SQL de claves candidatas para una lechería tolerando
     * sufijo "00". Devuelve string para usar en IN (...).
     */
    private function clavesCandidatas(string $lecher): string
    {
        $base = trim($lecher);
        $sinSufijo = preg_match('/00$/', $base) ? substr($base, 0, -2) : $base;

        $cands = array_unique(array_filter([
            $base,
            $sinSufijo,
            $sinSufijo . '00',
        ], fn($v) => $v !== ''));

        return implode(',', array_map(
            fn($c) => "'" . str_replace("'", "''", $c) . "'",
            $cands
        ));
    }

    public function obtenerHistorialLecheria($lecher)
    {
        $inList = $this->clavesCandidatas($lecher);

        $sql = "SELECT VENTA_LITROS AS VENTA_REAL, FIN_LITROS AS INVENTARIO_FINAL
                FROM inventarios_mensuales
                WHERE TRIM(CLAVE_LECHERIA) IN ($inList)
                ORDER BY ANIO_PERIODO DESC, MES_PERIODO DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerInventarioFinalMesAnterior(string $lecher, int $mes_actual, int $anio_actual): ?float
    {
        $mes_ant  = $mes_actual - 1;
        $anio_ant = $anio_actual;
        if ($mes_ant <= 0) { $mes_ant = 12; $anio_ant--; }

        $inList = $this->clavesCandidatas($lecher);

        $sql = "SELECT FIN_LITROS
                FROM inventarios_mensuales
                WHERE TRIM(CLAVE_LECHERIA) IN ($inList)
                  AND MES_PERIODO  = $mes_ant
                  AND ANIO_PERIODO = $anio_ant
                LIMIT 1";
        $row = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
        return $row ? floatval($row['FIN_LITROS'] ?? 0) : null;
    }

    public function calcularMesAnterior(int $mes_actual, int $anio_actual): array
    {
        $mes_ant  = $mes_actual - 1;
        $anio_ant = $anio_actual;
        if ($mes_ant <= 0) { $mes_ant = 12; $anio_ant--; }
        return ['mes' => $mes_ant, 'anio' => $anio_ant];
    }

    private function existeInventarioMes($lecheria, $mes, $anio)
    {
        if (empty($lecheria)) return false;
        $mes  = (int)$mes;
        $anio = (int)$anio;
        $inList = $this->clavesCandidatas($lecheria);

        // SOLO MES_PERIODO/ANIO_PERIODO. La FECHA es de captura y NO define el periodo
        // (de lo contrario un alta de junio con fecha en mayo dispara falsamente "ya existe").
        $sql = "SELECT ID FROM inventarios_mensuales
                WHERE TRIM(CLAVE_LECHERIA) IN ($inList)
                  AND ANIO_PERIODO = $anio
                  AND MES_PERIODO  = $mes
                LIMIT 1";
        $row = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['ID'] : false;
    }

    /**
     * NO-OP: INVENTARIO_LEP_SUBSIDIADA queda fuera del nuevo esquema.
     * Se mantiene la firma por compatibilidad con código existente.
     */
    private function upsertLepSubsidiada($lecher, $mes, $anio, $finLitros, $surtCajas, $ventaLitros, $regLitros): void
    {
        // Intencionalmente vacío. INVENTARIOS_MENSUALES es la única fuente.
    }

    public function guardar($datos, $usuario)
    {
        $lecheria_limpia = $datos['lecheria'] ?? 'SIN_CLAVE';

        $anio_actual = !empty($datos['anio_periodo']) ? (int)$datos['anio_periodo'] : (int)date('Y', strtotime($datos['fecha']));
        $mes_actual  = !empty($datos['mes_periodo'])  ? (int)$datos['mes_periodo']  : (int)date('m', strtotime($datos['fecha']));

        $idExistente = $this->existeInventarioMes($lecheria_limpia, $mes_actual, $anio_actual);
        if ($idExistente !== false) {
            return [
                'status'  => 'duplicado',
                'id'      => $idExistente,
                'mensaje' => 'Ya existe un inventario para este periodo. Cambiando a modo edición automáticamente.',
            ];
        }

        // Verificar mes anterior
        $mes_anterior  = $mes_actual - 1;
        $anio_anterior = $anio_actual;
        if ($mes_anterior <= 0) { $mes_anterior = 12; $anio_anterior--; }

        if (!$this->existeInventarioMes($lecheria_limpia, $mes_anterior, $anio_anterior) && empty($datos['confirmado_periodo'])) {
            $nombres_meses = ["", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
                              "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
            $nombre_mes_ant = $nombres_meses[$mes_anterior] ?? '';

            return [
                'status'  => 'requiere_confirmacion',
                'mensaje' => "Oye, te falta registrar el inventario de $nombre_mes_ant del $anio_anterior.\n\n¿Estás seguro de que quieres guardar este mes aunque falte el anterior?"
            ];
        }

        $pdf_nombre = "Inventario_{$lecheria_limpia}_{$anio_actual}_" . sprintf('%02d', $mes_actual) . ".pdf";

        // SQLite: USUARIO_CAPTURA (no USUARIO), sin DOTACION (no está en el esquema),
        // ID se asigna por AUTOINCREMENT.
        $sql = "INSERT INTO inventarios_mensuales (
                    FECHA, CLAVE_LECHERIA, CLAVE_TIENDA, ALMACEN, MUNICIPIO, COMUNIDAD,
                    SURT_FECHA, SURT_CAJAS, SURT_LITROS, SURT_FACTURA, SURT_CADUCIDAD,
                    INV_INI_CAJA, INV_INI_SOBRES, INV_INI_LITROS,
                    ABASTO_CAJA, ABASTO_SOBRES, ABASTO_LITROS,
                    VENTA_CAJA, VENTA_SOBRES, VENTA_LITROS,
                    REG_CAJA, REG_SOBRES, REG_LITROS,
                    DIF_CAJA, DIF_SOBRES, DIF_LITROS,
                    FIN_CAJA, FIN_SOBRES, FIN_LITROS,
                    HOGARES, MENORES, MAYORES,
                    PDF_RUTA, USUARIO_CAPTURA, ESTADO, MES_PERIODO, ANIO_PERIODO
                ) VALUES (
                    :fecha, :lecheria, :tienda, :almacen, :municipio, :comunidad,
                    :surt_fecha, :surt_cajas, :surt_litros, :surt_factura, :surt_caducidad,
                    :inv_ini_caja, :inv_ini_sobres, :inv_ini_litros,
                    :abasto_caja, :abasto_sobres, :abasto_litros,
                    :venta_caja, :venta_sobres, :venta_litros,
                    :reg_caja, :reg_sobres, :reg_litros,
                    :dif_caja, :dif_sobres, :dif_litros,
                    :fin_caja, :fin_sobres, :fin_litros,
                    :hogares, :menores, :mayores,
                    :pdf_ruta, :usuario, 'guardado', :mes, :anio
                )";

        $n = fn($v) => (int)($v ?? 0);

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':fecha'          => $datos['fecha'] ?? null,
                ':lecheria'       => $lecheria_limpia,
                ':tienda'         => $datos['tienda'] ?? null,
                ':almacen'        => $datos['almacen'] ?? null,
                ':municipio'      => $datos['municipio'] ?? null,
                ':comunidad'      => $datos['comunidad'] ?? null,
                ':surt_fecha'     => $datos['surt_fecha'] ?? null,
                ':surt_cajas'     => $n($datos['surt_cajas']),
                ':surt_litros'    => $n($datos['surt_litros']),
                ':surt_factura'   => $datos['surt_factura'] ?? null,
                ':surt_caducidad' => $datos['surt_caducidad'] ?? null,
                ':inv_ini_caja'   => $n($datos['inv_ini_caja']),
                ':inv_ini_sobres' => $n($datos['inv_ini_sobres']),
                ':inv_ini_litros' => $n($datos['inv_ini_litros']),
                ':abasto_caja'    => $n($datos['abasto_caja']),
                ':abasto_sobres'  => $n($datos['abasto_sobres']),
                ':abasto_litros'  => $n($datos['abasto_litros']),
                ':venta_caja'     => $n($datos['venta_caja']),
                ':venta_sobres'   => $n($datos['venta_sobres']),
                ':venta_litros'   => $n($datos['venta_litros']),
                ':reg_caja'       => $n($datos['reg_caja']),
                ':reg_sobres'     => $n($datos['reg_sobres']),
                ':reg_litros'     => $n($datos['reg_litros']),
                ':dif_caja'       => $n($datos['dif_caja']),
                ':dif_sobres'     => $n($datos['dif_sobres']),
                ':dif_litros'     => $n($datos['dif_litros']),
                ':fin_caja'       => $n($datos['fin_caja']),
                ':fin_sobres'     => $n($datos['fin_sobres']),
                ':fin_litros'     => $n($datos['fin_litros']),
                ':hogares'        => $n($datos['hogares']),
                ':menores'        => $n($datos['menores']),
                ':mayores'        => $n($datos['mayores']),
                ':pdf_ruta'       => $pdf_nombre,
                ':usuario'        => $usuario,
                ':mes'            => $mes_actual,
                ':anio'           => $anio_actual,
            ]);
            return ['status' => 'success', 'mensaje' => 'Guardado con éxito', 'id' => (int)$this->db->lastInsertId()];
        } catch (PDOException $e) {
            throw new Exception("Error SQLite: " . $e->getMessage());
        }
    }

    /** NO-OP: ya no se sincroniza con INVENTARIO_LEP_SUBSIDIADA. */
    public function syncLepSubsidiada($lecher, $mes, $anio, $datos): void { /* deprecated */ }

    public function buscarPorLecheria($clave, $mes = 0, $anio = 0)
    {
        $mes  = (int)$mes;
        $anio = (int)$anio;

        if ($mes < 1 || $mes > 12 || $anio < 2000) return [];

        $inList = $this->clavesCandidatas($clave);

        $sql = "SELECT ID, FECHA, MUNICIPIO, COMUNIDAD, FIN_CAJA, FIN_LITROS, ESTADO,
                       MES_PERIODO, ANIO_PERIODO
                FROM inventarios_mensuales
                WHERE TRIM(CLAVE_LECHERIA) IN ($inList)
                  AND ANIO_PERIODO = $anio
                  AND MES_PERIODO  = $mes
                ORDER BY ID DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $id = (int)$id;
        $stmt = $this->db->prepare("SELECT * FROM inventarios_mensuales WHERE ID = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
