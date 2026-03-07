# Dockerfile for PHP-Apache Setup

FROM php:8.1-apache

# Enable apache modules
RUN docker-php-ext-install mysqli

# Copy source files to the container
COPY . /var/www/html/

# Expose the port the app runs on
EXPOSE 80
