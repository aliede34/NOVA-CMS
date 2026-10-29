FROM php:8.2-apache

# Gerekli bağımlılıkları ve SQLite modülünü kur
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

# Apache mod_rewrite modülünü aktif et
RUN a2enmod rewrite

# Çalışma dizinini ayarla
WORKDIR /var/www/html

# Proje dosyalarını kopyala
COPY . /var/www/html/

# uploads ve veritabanı izinlerini ayarla
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 80
CMD ["apache2-foreground"]

