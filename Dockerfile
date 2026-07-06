FROM php:8.2-apache

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Set working directory
WORKDIR /var/www/html

# Copy project files to apache document root
COPY . /var/www/html/

# Pre-create files that PHP needs to write to, and set appropriate ownership for Apache
RUN touch heatindex.txt heatindex.html override_color.txt \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod 664 heatindex.txt heatindex.html override_color.txt

# Expose port 80 (Apache default inside container)
EXPOSE 80
