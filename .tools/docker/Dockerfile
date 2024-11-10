FROM php:8.3-fpm
RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y \
    curl \
    libfreetype-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    zlib1g-dev \
    libzip-dev \
    libcurl3-dev \
    unzip;

RUN docker-php-ext-install \
    curl \
    pdo_mysql \
    zip \
    && openssl;

RUN apt-get update -yqq \
    && apt-get install -y --no-install-recommends openssl \
    && sed -i -E 's/(CipherString\s*=\s*DEFAULT@SECLEVEL=)2/\11/' /etc/ssl/openssl.cnf \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer selfupdate

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN usermod -u 1000 www-data
RUN usermod -a -G www-data root
RUN chown -R www-data:www-data /var/www

WORKDIR /var/www

EXPOSE 9000

CMD ["php-fpm"]
