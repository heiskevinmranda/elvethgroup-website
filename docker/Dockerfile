FROM wordpress:7.1.3-php8.3-apache

ENV DEBIAN_FRONTEND=noninteractive

# ---------------------------------------------------------------------------
# System packages + PHP extensions required by Elementor / Astra / SureForms
# ---------------------------------------------------------------------------
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libwebp-dev \
        libonig-dev \
        libxml2-dev \
        libicu-dev \
        libmariadb-dev \
        less \
        mariadb-client \
        ca-certificates \
        curl; \
    for ext in mysqli pdo_mysql gd zip exif intl opcache; do \
        if ! php -m | grep -qix "$ext"; then \
            if [ "$ext" = "gd" ]; then \
                docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
            fi; \
            docker-php-ext-install -j"$(nproc)" "$ext"; \
        fi; \
    done; \
    apt-get purge -y --auto-remove libzip-dev libpng-dev libjpeg62-turbo-dev \
        libfreetype6-dev libwebp-dev libonig-dev libxml2-dev libicu-dev libmariadb-dev; \
    apt-get autoremove -y; \
    rm -rf /var/lib/apt/lists/*

# ---------------------------------------------------------------------------
# WP-CLI (used by the deploy script for cache flush / option sync)
# ---------------------------------------------------------------------------
RUN set -eux; \
    curl -fsSL -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar; \
    chmod +x /usr/local/bin/wp; \
    wp --info --allow-root

# ---------------------------------------------------------------------------
# Application code: themes, plugins, mu-plugins, uploads seed, rewrite rules.
# WP core itself is shipped by the base image (7.1.3) and is not overwritten.
# ---------------------------------------------------------------------------
COPY docker/php.ini /usr/local/etc/php/conf.d/elveth.ini
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/elveth-entrypoint
RUN chmod +x /usr/local/bin/elveth-entrypoint

COPY .htaccess /var/www/html/.htaccess
COPY wp-content/ /var/www/html/wp-content/

RUN set -eux; \
    chown -R www-data:www-data /var/www/html/wp-content; \
    find /var/www/html/wp-content -type d -exec chmod 755 {} \;; \
    find /var/www/html/wp-content -type f -exec chmod 644 {} \;; \
    chown www-data:www-data /var/www/html/.htaccess

ENTRYPOINT ["/usr/local/bin/elveth-entrypoint"]
CMD ["apache2-foreground"]
