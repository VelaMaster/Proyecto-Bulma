#!/bin/sh
# entrypoint.sh — Se ejecuta al iniciar el contenedor, DESPUÉS de montar volúmenes.
# Garantiza que www-data pueda escribir en los directorios de datos.

set -e

# Subdirs runtime que Apache necesita escribir.
# IMPORTANTE: NO hacer `chown -R` sobre /var/www/html/datos porque es bind-mount
# y los cambios se propagan al host → rompe `git pull` (EACCES sobre archivos
# trackeados que quedaron como www-data 750). Solo tocamos los subdirs runtime.
RUNTIME_DIRS="
sesiones
promotores/requerimientos
promotores/reportes
promotores/pdfs
distribucion/minutas
distribucion/req_precio
distribucion/ope
supervisor/inventarios_almacen
supervisores/requerimientos_dotacion
"

for d in $RUNTIME_DIRS; do
    mkdir -p "/var/www/html/datos/$d"
    chown www-data:www-data "/var/www/html/datos/$d" 2>/dev/null || true
    # 2775 = setgid + rwx grupo → archivos nuevos heredan grupo www-data
    chmod 2775 "/var/www/html/datos/$d" 2>/dev/null || true
done

# Sesiones: solo www-data (contienen IDs activos)
chmod 700 /var/www/html/datos/sesiones 2>/dev/null || true

# BDD SQLite escribible por Apache (sin tocar dueño si ya existe en host)
if [ -f /var/www/html/datos/local.db ]; then
    chmod 664 /var/www/html/datos/local.db 2>/dev/null || true
fi

# Bloquear acceso web al directorio de sesiones
cat > /var/www/html/datos/sesiones/.htaccess << 'EOF'
Deny from all
EOF

echo "[entrypoint] Permisos de datos/sesiones configurados."

# Iniciar Apache en foreground (comportamiento original del contenedor)
exec apache2-foreground
