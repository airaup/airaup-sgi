FROM php:5.6.36-apache

MAINTAINER Martin Becerra <mrtnbcrr@gmail.com>

RUN \
    apt-get update && \
    docker-php-ext-install mbstring pdo pdo_mysql mysql mysqli

COPY src/ /var/www/html/
