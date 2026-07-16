# Dockerfile để deploy lên Render.com (hoặc bất kỳ PaaS nào chạy Docker).
# Dùng PHP built-in server (`php artisan serve`) để giữ đơn giản, phù hợp
# quy mô nội bộ 1 công ty nhỏ. Nếu sau này nhiều người dùng cùng lúc, nên
# chuyển sang Nginx + PHP-FPM.
FROM php:8.3-cli

# Thư viện hệ thống + extension PHP cần cho Laravel và kết nối Postgres (Supabase)
RUN apt-get update && apt-get install -y \
        libpq-dev libzip-dev zip unzip git curl \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

# Node.js để build Tailwind/Vite, Composer để cài dependency PHP
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && apt-get install -y nodejs
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN npm install && npm run build

RUN chmod -R 775 storage bootstrap/cache

EXPOSE 10000
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
