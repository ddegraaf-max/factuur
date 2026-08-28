# EasyInvoice — production Docker image for Railway.
# Based on FrankenPHP (same as Railway's Railpack default) but with full control
# over PHP extensions. Adds GD which DomPDF needs for rendering logos.

FROM dunglas/frankenphp:php8.2-bookworm

# ----- PHP extensions -----
# install-php-extensions is bundled in the FrankenPHP image and handles
# extension installation correctly for compiled-from-source PHP.
RUN install-php-extensions \
    gd \
    pdo_pgsql \
    intl \
    zip \
    bcmath \
    exif \
    opcache

# ----- PHP-productie-instellingen -----
# validate_timestamps=0 is veilig: de code in een container wijzigt nooit na
# de deploy. Scheelt per request het her-checken (en zonder opcache zelfs
# her-compileren) van duizenden PHP-bestanden.
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=192'; \
        echo 'opcache.interned_strings_buffer=24'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'realpath_cache_size=4096K'; \
        echo 'realpath_cache_ttl=600'; \
    } > "${PHP_INI_DIR:-/usr/local/etc/php}/conf.d/zz-production.ini"

# ----- System tools + Node.js for frontend build -----
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    git \
    unzip \
    gnupg \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    # pg_dump voor de dagelijkse back-up (backup:run). PGDG-versie 17 dumpt
    # elke oudere Postgres-server; de Debian-eigen client 15 zou weigeren
    # bij een nieuwere server ("server version mismatch").
    && curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor -o /usr/share/keyrings/pgdg.gpg \
    && echo "deb [signed-by=/usr/share/keyrings/pgdg.gpg] https://apt.postgresql.org/pub/repos/apt bookworm-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
    && apt-get update && apt-get install -y --no-install-recommends postgresql-client-17 \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ----- Composer -----
# Niet via `COPY --from=composer:2`: dat vergt een extra image-resolutie bij
# Docker Hub, die op de Railway-builder af en toe wegvalt ("context canceled")
# en dan de hele build sloopt. De phar direct ophalen scheelt die afhankelijkheid
# en kan hier wél retryen.
RUN curl -fsSL --retry 5 --retry-all-errors \
        https://getcomposer.org/download/latest-2.x/composer.phar -o /usr/bin/composer \
    && chmod +x /usr/bin/composer \
    && composer --version

WORKDIR /app

# ----- Composer dependencies (cache layer: copy lock files first) -----
# Geen geheugenlimiet: zonder composer.lock doet 'install' een volledige
# dependency-resolutie die anders out-of-memory kan gaan op de builder.
ENV COMPOSER_MEMORY_LIMIT=-1
COPY composer.json composer.lock* ./
# Retry de install: GitHub API geeft af en toe een transient 504 bij het
# downloaden van een package-zipball, wat anders de hele build laat falen.
RUN composer config --global policy.advisories.block false && \
    for i in 1 2 3 4 5; do \
        echo "composer install — poging $i/5"; \
        composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --no-progress --prefer-dist && exit 0; \
        echo "composer install faalde (poging $i/5), opnieuw over 10s..."; \
        sleep 10; \
    done; \
    echo "composer install bleef falen na 5 pogingen" && exit 1

# ----- NPM dependencies -----
COPY package.json package-lock.json* ./
RUN if [ -f package-lock.json ]; then npm ci --no-audit --no-fund; else npm install --no-audit --no-fund; fi

# ----- Copy application code -----
COPY . .

# ----- Build frontend assets -----
RUN npm run build && rm -rf node_modules

# ----- Finalize composer (autoload with full source) -----
RUN composer dump-autoload --optimize --no-scripts --classmap-authoritative

# ----- Permissions for Laravel -----
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# ----- Railway uses $PORT — tell FrankenPHP to listen on it -----
# SERVER_NAME is a static fallback; the real (runtime) port is set by the CMD
# below via --listen :$PORT, so $PORT must NOT be referenced at build time here.
ENV SERVER_NAME=":8080"
EXPOSE 8080

# Default: serve /app/public via FrankenPHP php-server.
# Migrations run via railway.json preDeployCommand.
# Bij het opstarten eerst de Laravel-caches opbouwen (config + views), zodat
# niet elke request het hele framework opnieuw hoeft te configureren. Faalt
# een cache-stap, dan start de server gewoon zonder (|| true).
# De Laravel-scheduler (herinneringen, terugkerende facturen, dagoverzicht,
# btw-herinnering, demo-opschoning, merkdossier …) draait als achtergrond-
# proces náást de webserver. Railway heeft één container; zonder dit proces
# draait er géén enkele geplande taak. Uitvoer gaat naar dezelfde stdout.
CMD ["sh", "-c", "php artisan config:cache || true; php artisan view:cache || true; (php artisan schedule:work --no-ansi 2>&1 | sed -u 's/^/[scheduler] /' &); exec frankenphp php-server --listen :${PORT:-8080} --root /app/public"]
