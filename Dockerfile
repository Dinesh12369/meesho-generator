FROM php:8.2-cli

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && docker-php-ext-install pdo pdo_mysql

# Enable required extensions
RUN docker-php-ext-install curl

# Set working directory
WORKDIR /app

# Copy application files
COPY . /app

# Create necessary directories
RUN mkdir -p /app/data/generated /app/assets/discount_tags \
    && chmod -R 777 /app/data

# Expose port
EXPOSE 8080

# Start PHP built-in server
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app"]
