FROM ubuntu:22.04

RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y php8.1 php8.1-mysql \ 
    php-cli curl php-curl php-xml php-mbstring  php-zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /scripts
RUN curl -sS https://getcomposer.org/installer -o composer-setup.php
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN composer self-update
RUN rm -f composer-setup.php
COPY composer ./
RUN composer update

