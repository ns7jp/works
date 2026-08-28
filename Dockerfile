FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev libsqlite3-dev \
    && docker-php-ext-install mbstring pdo_sqlite \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod headers rewrite

COPY docker/apache-security.conf /etc/apache2/conf-available/pulse-security.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/pulse.ini
RUN a2enconf pulse-security

COPY index.php post.php profile.php login.php register.php logout.php health.php /var/www/html/
COPY api/ /var/www/html/api/
COPY config/ /var/www/html/config/
COPY includes/ /var/www/html/includes/
COPY public/ /var/www/html/public/
RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html/data

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
  CMD php -r '$r=@file_get_contents("http://127.0.0.1/health.php"); $j=json_decode($r ?: "", true); exit(is_array($j) && ($j["status"] ?? null) === "ok" ? 0 : 1);'
