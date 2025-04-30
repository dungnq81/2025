# Sử dụng hình ảnh Ubuntu 22.04
FROM ubuntu:22.04

# Đặt môi trường không yêu cầu tương tác
ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=Asia/Ho_Chi_Minh
ENV APACHE_LOG_DIR=/var/log/apache2

# Cập nhật hệ thống và cài đặt các gói cơ bản
RUN apt-get update && apt-get install -y \
    software-properties-common \
    tzdata \
    curl \
    && apt-get clean

# Thêm kho lưu trữ PPA của Ondrej để cài đặt PHP
RUN add-apt-repository ppa:ondrej/php \
    && apt-get update

# Cài đặt Apache2, PHP 8.2 và các mô-đun cần thiết
RUN apt-get install -y \
    apache2 \
    php8.2 \
    php8.2-mysql \
    libapache2-mod-php8.2 \
    php8.2-cli \
    php8.2-curl \
    php8.2-common \
    php8.2-zip \
    php8.2-gd \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-bcmath \
    -o Dpkg::Options::="--force-confdef" \
    -o Dpkg::Options::="--force-confold" \
    && apt-get clean

# Kích hoạt các mô-đun cần thiết của Apache
RUN a2enmod rewrite

# Nếu bạn đã tạo file cấu hình virtual host, kích hoạt nó (nếu cần)
# RUN a2ensite 000-default.conf

# Mở cổng 80
EXPOSE 80

# Khởi động Apache khi container bắt đầu
CMD ["apachectl", "-D", "FOREGROUND"]
