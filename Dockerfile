# Usa la imagen oficial de PHP 7.4 con Apache
FROM php:7.4-apache

# 1. Copia tu archivo de configuración personalizado de Apache
#    Esto es lo que activa tu .htaccess al establecer "AllowOverride All"
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# 2. Habilita el módulo de reescritura de Apache
RUN a2enmod rewrite

# 3. Instala las extensiones de PHP necesarias para la base de datos
RUN docker-php-ext-install pdo pdo_mysql

# 4. Establece el directorio de trabajo
WORKDIR /var/www/html

# 5. Copia todos los archivos de tu aplicación al directorio de trabajo
COPY . .

# 6. (Mejora) Asigna la propiedad de los archivos al usuario de Apache.
#    Es más seguro y correcto que usar chmod 777.
RUN chown -R www-data:www-data /var/www/html

# 7. Expone el puerto 80 para que CapRover pueda dirigir el tráfico
EXPOSE 80

# El CMD por defecto de la imagen ya es "apache2-foreground", así que no necesitas añadirlo.