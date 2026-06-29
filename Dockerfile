FROM php:8.2-apache

# Instalamos dependencias del sistema para el cliente de Firebird + libzip para XLSX.
# firebird3.0-utils incluye gbak/gfix/isql que se usan para CLONAR la BDD real de
# Liconsa hacia el contenedor (backup remoto + restore local). Si el paquete 3.0
# no estuviera disponible, el ServicioClonadorFirebird detecta el binario en
# /usr/bin con `command -v` y registra error claro en errores_log.
RUN apt-get update && apt-get install -y \
    libfbclient2 \
    firebird-dev \
    firebird3.0-utils \
    libsqlite3-dev \
    libzip-dev \
    zip \
    && rm -rf /var/lib/apt/lists/*

# Firebird PDO (necesita compilación)
RUN docker-php-ext-install pdo_firebird

# SQLite PDO — en php:8.2-apache el .so ya existe compilado; solo hay que habilitarlo.
# Si por algún motivo no existe, el fallback lo compila desde libsqlite3-dev.
RUN docker-php-ext-enable pdo_sqlite 2>/dev/null || docker-php-ext-install pdo_sqlite

# Extensión zip para generar XLSX (OPE Diconsa desde plantilla)
RUN docker-php-ext-install zip

# Activamos rewrite para las rutas de tu app
RUN a2enmod rewrite
RUN a2enmod headers

# Entrypoint que fija permisos de datos/sesiones al arrancar (post-volume-mount)
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]