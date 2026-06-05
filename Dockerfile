FROM php:8.2-apache

# Instalamos dependencias del sistema para el cliente de Firebird + libzip para XLSX
RUN apt-get update && apt-get install -y \
    libfbclient2 \
    firebird-dev \
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