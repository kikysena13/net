FROM php:8.3-cli

RUN docker-php-ext-install mbstring pdo_mysql

WORKDIR /app
COPY . /app

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t /app"]