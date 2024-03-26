FROM php:8.2-apache
ENV PORT 80

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ADD docker/apache-config.conf /etc/apache2/sites-enabled/000-default.conf
RUN sed -i "s/80/\$\{PORT\}/g" /etc/apache2/sites-enabled/000-default.conf /etc/apache2/ports.conf
RUN a2enmod rewrite
RUN a2enmod headers

RUN apt-get update && apt-get install -y \
    libzip-dev \
    vim \
#    default-mysql-client \
    zlib1g-dev \
    libzip-dev \
    unzip \
&& rm -rf /var/lib/apt/lists/* \


RUN pecl install excimer
RUN docker-php-ext-install zip

WORKDIR /var/www/html/

COPY composer.json composer.lock ./
COPY .env.example .env ./
COPY . .

RUN composer install
RUN chmod +x artisan


RUN chown -R www-data:www-data \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache
#
RUN php artisan key:generate