FROM php:8.3.30-apache

# ===============================
# System & PHP extensions
# ===============================
RUN apt-get update && apt-get install -y \
    freetds-dev \
    freetds-bin \
    freetds-common \
    unixodbc \
    libsybdb5 \
    libpq-dev \
    libzip-dev \
    libjpeg-dev \
    libpng-dev \
    libfreetype6-dev \
    openssl \
    zip \
    unzip \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli \
    pdo_dblib \
    gd \
    zip \
    opcache \
 && rm -rf /var/lib/apt/lists/*

# ===============================
# Apache modules
# ===============================
RUN a2enmod ssl rewrite headers deflate

# ===============================
# Set working dir (optional but nice)
# ===============================
WORKDIR /var/www/html

# ===============================
# Expose ports
# ===============================
EXPOSE 80 443
