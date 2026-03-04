FROM php:8.2-apache

# Instalamos las librerías del sistema necesarias para Firebird
RUN apt-get update && apt-get install -y \
    libib-util \
    firebird-dev \
    && docker-php-ext-install pdo_firebird

# Activamos el modo rewrite de Apache para Bulma/Rutas
RUN a2enmod rewrite

# Reiniciamos Apache para aplicar cambios
RUN service apache2 restart
