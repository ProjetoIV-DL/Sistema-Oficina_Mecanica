FROM mcr.microsoft.com/devcontainers/php:8.3

RUN apt-get update \
    && apt-get install -y mariadb-client \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*