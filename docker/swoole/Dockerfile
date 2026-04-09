FROM php:8.5-cli

LABEL maintainer="ant-smorodin@ya.ru"

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    wget \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libzip-dev \
    libxml2-dev \
    libicu-dev \
    zlib1g-dev \
    libpq-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
    && pecl install openswoole && docker-php-ext-enable openswoole

COPY --from=composer:2.9.5 /usr/bin/composer /usr/bin/composer

#ADD docker/php/php.ini /usr/local/etc/php/conf.d/40-custom.ini
#ADD docker/php/output.conf /usr/local/etc/php/conf.d/output.conf
#ADD docker/php/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf

COPY . /var/www/html
WORKDIR /var/www/html

EXPOSE 9501/tcp

#CMD ["php-fpm"]

CMD php swoole.php