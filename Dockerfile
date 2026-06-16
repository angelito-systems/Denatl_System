FROM php:8.4-fpm-bullseye

# Instalar dependencias del sistema y Chromium para Spatie PDF
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    supervisor \
    cron \
    chromium \
    fonts-liberation \
    libnss3 \
    libxss1 \
    libasound2 \
    libx11-xcb1 \
    libzip-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones de PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip soap

# Instalar Redis extension usando PECL
RUN pecl install redis && docker-php-ext-enable redis

# Instalar Node.js para Vite y Spatie PDF
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Copiar dependencias de Composer primero
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-dev --optimize-autoloader --no-scripts

# Copiar código fuente
COPY . .

# Crear entorno temporal para que Laravel pueda bootear durante el build (requerido por Wayfinder)
RUN rm -rf bootstrap/cache/*.php \
    && cp .env.example .env \
    && php artisan key:generate

# Instalar dependencias npm (Puppeteer se descargara normalmente)
RUN npm ci

# Compilar assets (Wayfinder requiere PHP instalado durante el build de Vite)
RUN npm run build

# Opcional: remover dependencias dev de Node para ahorrar algo de espacio
RUN npm prune --omit=dev

# Copiar configuraciones
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Configurar cron para el Scheduler (se ejecuta cada minuto)
RUN echo "* * * * * root cd /var/www/html && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1" >> /etc/cron.d/laravel-cron
RUN chmod 0644 /etc/cron.d/laravel-cron
RUN crontab /etc/cron.d/laravel-cron

EXPOSE 9000

# Usar Supervisor para iniciar PHP-FPM, Colas y Cron
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
