FROM php:8.2-apache

# تثبيت متطلبات ومكتبات PHP اللازمة
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev zip unzip git curl libonig-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring gd zip bcmath

# تفعيل ميزة التوجيه في أباتشي
RUN a2enmod rewrite

# ضبط مسار التشغيل ليشير إلى مجلد public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# تثبيت Composer ونسخ ملفات المشروع
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .

# تثبيت حزم المشروع
RUN composer install --no-dev --optimize-autoloader

# إعطاء صلاحيات التخزين للارافل
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

# أوامر التشغيل والربط
CMD php artisan config:clear && php artisan migrate --force && php artisan storage:link && apache2-foreground