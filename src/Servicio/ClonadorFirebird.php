<?php
// src/Servicio/ClonadorFirebird.php
// Clona la BDD Firebird REAL de Liconsa (172.24.10.251 / C:/SisDLL20/BD/DB_SIDIST.FDB)
// hacia el Firebird del contenedor Docker (db / /firebird/data/DB_SIDISTLOCAL.FDB),
// usando gbak (Firebird backup tool).
//
// Flujo:
//   1) gbak -B  remoto  -> /tmp/liconsa_YYYYMMDD_HHMM.fbk     (backup transferido por TCP/3050)
//   2) gbak -C  fbk     -> /firebird/data/DB_SIDISTLOCAL.FDB  (restore en el contenedor)
//   3) Cualquier fallo se registra en errores_log con stderr completo.
//
// Por qué gbak y no copia binaria:
//   - El .fdb del servidor está abierto/activo. Copiarlo en caliente lo deja inconsistente.
//   - gbak genera un backup transaccionalmente consistente sin tirar la BDD origen.
//
// Diseñado para ejecutarse desde admin (botón) o desde cron.

require_once __DIR__ . '/../Database/DatabaseSQLite.php';
require_once __DIR__ . '/LoggerErrores.php';

class ClonadorFirebird
{
    private string $remHost;
    private string $remPort;
    private string $remPath;
    private string $remUser;
    private string $remPass;

    private string $locHost;
    private string $locPath;
    private string $locUser;
    private string $locPass;

    private string $backupDir;
    private string $gbakBin;

    public function __construct()
    {
        $this->remHost = (string)DatabaseSQLite::getConfig('fb_host_remoto',    '172.24.10.251');
        $this->remPort = (string)DatabaseSQLite::getConfig('fb_port',           '3050');
        $this->remPath = (string)DatabaseSQLite::getConfig('fb_db_path_remoto', 'C:/SisDLL20/BD/DB_SIDIST.FDB');
        $this->remUser = (string)DatabaseSQLite::getConfig('fb_user_remoto',    'SYSDBA');
        $this->remPass = (string)DatabaseSQLite::getConfig('fb_pass_remoto',    '290990');

        $this->locHost = (string)DatabaseSQLite::getConfig('fb_host_local',     'localhost');
        $this->locPath = (string)DatabaseSQLite::getConfig('fb_db_path_local',  '/firebird/data/DB_SIDISTLOCAL.FDB');
        $this->locUser = (string)DatabaseSQLite::getConfig('fb_user_local',     'SYSDBA');
        $this->locPass = (string)DatabaseSQLite::getConfig('fb_pass_local',     'masterkey');

        // Directorio para .fbk temporal. Se rota: conservamos los 3 últimos.
        $this->backupDir = (string)DatabaseSQLite::getConfig('fb_backup_dir', '/tmp/liconsa_backups');
        $this->gbakBin   = $this->resolverGbak();
    }

    /** Devuelve la ruta absoluta a gbak o '' si no está instalado. */
    private function resolverGbak(): string
    {
        $candidatos = ['/usr/bin/gbak', '/usr/local/bin/gbak'];
        foreach ($candidatos as $c) if (is_executable($c)) return $c;
        $out = shell_exec('command -v gbak 2>/dev/null');
        return is_string($out) ? trim($out) : '';
    }

    /**
     * Ejecuta el flujo completo. Devuelve un resumen con todas las etapas.
     * NO lanza excepciones: cualquier fallo queda registrado en errores_log y
     * reflejado en el array de retorno con ok=false.
     */
    public function clonarRemotoHaciaLocal(): array
    {
        $t0 = microtime(true);
        $resumen = [
            'ok'         => false,
            'etapa'      => 'init',
            'fbk'        => null,
            'bytes_fbk'  => 0,
            'duracion_ms'=> 0,
            'salida'     => [],
            'mensaje'    => '',
        ];

        try {
            // 1) Pre-requisitos
            if ($this->gbakBin === '') {
                throw new RuntimeException(
                    'gbak no está instalado en el contenedor. Reconstruye la imagen con firebird3.0-utils en el Dockerfile.'
                );
            }
            if (!is_dir($this->backupDir)) {
                @mkdir($this->backupDir, 0775, true);
                if (!is_dir($this->backupDir)) {
                    throw new RuntimeException("No se pudo crear $this->backupDir");
                }
            }
            if (!is_writable(dirname($this->locPath))) {
                throw new RuntimeException(
                    "El directorio destino no es escribible: " . dirname($this->locPath) .
                    " — revisa permisos del volumen ./database del docker-compose."
                );
            }

            // 2) Backup remoto -> .fbk local
            $resumen['etapa'] = 'backup';
            $stamp = date('Ymd_Hi');
            $fbk   = "{$this->backupDir}/liconsa_{$stamp}.fbk";
            $resumen['fbk'] = $fbk;

            $cmdBackup = sprintf(
                '%s -B -USER %s -PAS %s -SERVICE %s:service_mgr %s %s 2>&1',
                escapeshellcmd($this->gbakBin),
                escapeshellarg($this->remUser),
                escapeshellarg($this->remPass),
                escapeshellarg("{$this->remHost}/{$this->remPort}"),
                escapeshellarg("{$this->remHost}/{$this->remPort}:{$this->remPath}"),
                escapeshellarg($fbk)
            );
            $resumen['salida'][] = "[backup] cmd: " . $this->cmdSinPass($cmdBackup);
            [$rc, $stdout] = $this->ejecutar($cmdBackup);
            $resumen['salida'][] = "[backup] rc=$rc";
            foreach (preg_split('/\r?\n/', trim((string)$stdout)) as $l) {
                if ($l !== '') $resumen['salida'][] = "[backup] $l";
            }
            if ($rc !== 0 || !file_exists($fbk) || filesize($fbk) === 0) {
                throw new RuntimeException("gbak -B falló (rc=$rc): " . substr((string)$stdout, 0, 800));
            }
            $resumen['bytes_fbk'] = (int)filesize($fbk);

            // 3) Restore .fbk -> docker FDB (sobreescribe con -REP)
            $resumen['etapa'] = 'restore';
            $cmdRestore = sprintf(
                '%s -C -REP -USER %s -PAS %s %s %s 2>&1',
                escapeshellcmd($this->gbakBin),
                escapeshellarg($this->locUser),
                escapeshellarg($this->locPass),
                escapeshellarg($fbk),
                escapeshellarg("{$this->locHost}/{$this->remPort}:{$this->locPath}")
            );
            $resumen['salida'][] = "[restore] cmd: " . $this->cmdSinPass($cmdRestore);
            [$rc2, $stdout2] = $this->ejecutar($cmdRestore);
            $resumen['salida'][] = "[restore] rc=$rc2";
            foreach (preg_split('/\r?\n/', trim((string)$stdout2)) as $l) {
                if ($l !== '') $resumen['salida'][] = "[restore] $l";
            }
            if ($rc2 !== 0) {
                throw new RuntimeException("gbak -C falló (rc=$rc2): " . substr((string)$stdout2, 0, 800));
            }

            // 4) Rotar backups: mantener los últimos 3
            $this->rotarBackups(3);

            // 5) Listo
            $resumen['etapa']       = 'done';
            $resumen['ok']          = true;
            $resumen['duracion_ms'] = (int)((microtime(true) - $t0) * 1000);
            $resumen['mensaje']     = sprintf(
                'Clonado correcto: %s bytes en backup, %d ms total.',
                number_format($resumen['bytes_fbk']), $resumen['duracion_ms']
            );

            LoggerErrores::registrar(
                'sync', 'info',
                "Clonado Firebird Liconsa -> Docker OK ({$resumen['bytes_fbk']} bytes, {$resumen['duracion_ms']} ms)",
                __FILE__, __LINE__,
                ['fbk' => $fbk, 'remoto' => "{$this->remHost}:{$this->remPath}", 'local' => $this->locPath]
            );
        } catch (\Throwable $e) {
            $resumen['duracion_ms'] = (int)((microtime(true) - $t0) * 1000);
            $resumen['mensaje']     = $e->getMessage();
            LoggerErrores::registrar(
                'sync', 'error',
                'Clonado Firebird Liconsa -> Docker FALLÓ en etapa ' . $resumen['etapa'] . ': ' . $e->getMessage(),
                __FILE__, __LINE__,
                [
                    'etapa'    => $resumen['etapa'],
                    'remoto'   => "{$this->remHost}:{$this->remPath}",
                    'local'    => $this->locPath,
                    'gbak_bin' => $this->gbakBin ?: '(no instalado)',
                    'salida'   => implode("\n", $resumen['salida']),
                ]
            );
        }
        return $resumen;
    }

    /** Ejecuta un comando shell capturando rc y salida combinada. */
    private function ejecutar(string $cmd): array
    {
        $output = [];
        $rc     = 0;
        exec($cmd, $output, $rc);
        return [$rc, implode("\n", $output)];
    }

    /** Oculta la contraseña en el log para no exponerla. */
    private function cmdSinPass(string $cmd): string
    {
        $sanitized = preg_replace("/-PAS '[^']+'/", "-PAS '***'", $cmd);
        return $sanitized ?? $cmd;
    }

    /** Conserva los N backups más recientes y borra el resto. */
    private function rotarBackups(int $conservar): void
    {
        $files = glob($this->backupDir . '/liconsa_*.fbk') ?: [];
        if (count($files) <= $conservar) return;
        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
        foreach (array_slice($files, $conservar) as $viejo) {
            @unlink($viejo);
        }
    }

    /** Pequeño helper para mostrar la última fecha de clonado. */
    public function ultimoClonado(): ?string
    {
        $files = glob($this->backupDir . '/liconsa_*.fbk') ?: [];
        if (!$files) return null;
        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
        return date('Y-m-d H:i:s', filemtime($files[0])) . ' (' . basename($files[0]) . ')';
    }
}
