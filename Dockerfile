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

# ----- System tools + Node.js for frontend build -----
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    git \
    unzip \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ----- Composer -----
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# ----- Composer dependencies (cache layer: copy lock files first) -----
# Geen geheugenlimiet: zonder composer.lock doet 'install' een volledige
# dependency-resolutie die anders out-of-memory kan gaan op de builder.
ENV COMPOSER_MEMORY_LIMIT=-1
COPY composer.json composer.lock* ./
RUN composer config --global policy.advisories.block false && \
    composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --no-progress --prefer-dist

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
# FrankenPHP reads SERVER_NAME for binding; fall back to :8080 for local
ENV SERVER_NAME=":${PORT:-8080}"
EXPOSE 8080

# Default: serve /app/public via FrankenPHP php-server.
# Migrations run via railway.json preDeployCommand.
CMD ["sh", "-c", "frankenphp php-server --listen :${PORT:-8080} --root /app/public"]
