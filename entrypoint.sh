#!/bin/sh
# entrypoint.sh — Se ejecuta al iniciar el contenedor, DESPUÉS de montar volúmenes.
# Garantiza que www-data pueda escribir en los directorios de datos.

set -e

# Crear directorios necesarios si no existen
mkdir -p /var/www/html/datos/sesiones
mkdir -p /var/www/html/datos/promotores/requerimientos
mkdir -p /var/www/html/datos/promotores/reportes
mkdir -p /var/www/html/datos/promotores/pdfs

# Dar permisos de escritura a www-data (uid 33 en Debian/Apache)
chown -R www-data:www-data /var/www/html/datos
chmod -R 750 /var/www/html/datos
chmod -R 700 /var/www/html/datos/sesiones

# Bloquear acceso web al directorio de sesiones
cat > /var/www/html/datos/sesiones/.htaccess << 'EOF'
Deny from all
EOF

echo "[entrypoint] Permisos de datos/sesiones configurados."

# Iniciar Apache en foreground (comportamiento original del contenedor)
exec apache2-foreground
