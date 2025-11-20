FROM php:apache

# Prepare APT for package installation
RUN apt update && apt upgrade -y

# Install zlib and ICU libs
RUN apt install -y zlib1g zlib1g-dev libicu-dev libzip-dev

# Install required PHP extensions
RUN docker-php-ext-install mysqli fileinfo zip intl

# Copy Apache/PHP config
COPY ./docker/server/httpd.conf /etc/apache2/httpd.conf
COPY ./docker/server/mod_rewrite.load /etc/apache2/mods-enabled/mod_rewrite.load
COPY ./docker/server/mod_headers.load /etc/apache2/mods-enabled/mod_headers.load
COPY ./docker/server/vhost-default.conf /etc/apache2/sites-available/000-default.conf
COPY ./docker/server/dir-index.conf /etc/apache2/mods-enabled/dir.conf
COPY ./docker/server/php.ini /usr/local/etc/php/php.ini

# Global ServerName to suppress FQDN warning
COPY ./docker/server/servername.conf /etc/apache2/conf-available/servername.conf
RUN a2enconf servername.conf

# Copy app files
COPY ./public /var/www/html/

# Installer config templates
COPY ./scripts/install/Config.ini /var/www/html/Config.ini
COPY ./scripts/install/themeConfig.ini /var/www/html/admin/site/UI/config.ini
COPY ./scripts/install/moduleConfig.ini /var/www/html/admin/site/modules/config.ini
COPY ./scripts/install/extConfig.ini /var/www/html/admin/site/extensions/config.ini

# Permissions & cleanup
RUN chown -R www-data:www-data /var/www && \
    rm -rf /var/www/html/admin/site/UI/test_theme && \
    rm -rf /var/www/html/admin/site/modules/example

EXPOSE 80
