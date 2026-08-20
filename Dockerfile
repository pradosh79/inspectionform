FROM php:8.2-apache

# mysqli is what config.php uses. gd is optional but common for image work.
RUN docker-php-ext-install mysqli \
    && a2enmod rewrite

# Copy the app in
COPY . /var/www/html/

# Let .htaccess (Options -Indexes, file deny rules) actually take effect
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Railway provides a $PORT env var; make Apache listen on it instead of 80
ENV PORT=8080
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/000-default.conf

# document/ is written to at runtime; make it writable
RUN mkdir -p /var/www/html/document \
    && chown -R www-data:www-data /var/www/html/document

CMD ["apache2-foreground"]
