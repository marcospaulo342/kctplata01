FROM php:8.2-apache

# Instala extensões necessárias
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Copia todos os arquivos da pasta para o servidor Apache
COPY . /var/www/html/

# Dá permissão para os arquivos
RUN chown -R www-data:www-data /var/www/html

# Define a porta
EXPOSE 80
