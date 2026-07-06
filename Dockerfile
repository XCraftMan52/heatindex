FROM php:8.2.32-alpine

# 1. Install standard system tools if needed, but no Apache required
RUN apk update && apk upgrade

# 2. Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

# 3. Bake project files directly
COPY . /var/www/html/

# 4. Handle your tracking/dynamic files with standard Alpine 'www-data' user
RUN touch heatindex.txt heatindex.html override_color.txt \
    && chown -R www-data:www-data /var/www/html \
    && chmod 664 heatindex.txt heatindex.html override_color.txt \
    && chmod 755 /var/www/html

EXPOSE 80

# 5. Start PHP's built-in web server listening on port 80
CMD ["php", "-S", "0.0.0.0:80", "-t", "/var/www/html"]