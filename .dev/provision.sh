#!/bin/bash

# Set non-interactive mode to avoid prompts
export DEBIAN_FRONTEND=noninteractive

# Update and upgrade system
sudo apt-get update -y && sudo apt-get upgrade -y
sudo apt-get install -y software-properties-common tzdata debconf-utils wget curl unzip git

# Add Ondrej's PPA for PHP 8.2
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update -y

# Ensure .bashrc exists and add alias if not already present
BASHRC="/home/vagrant/.bashrc"
[ ! -f "$BASHRC" ] && touch "$BASHRC"

grep -qxF "alias wp='/var/www/html/vendor/bin/wp'" "$BASHRC" || echo "alias wp='/var/www/html/vendor/bin/wp'" >> "$BASHRC"
grep -qxF "export PATH=\$PATH:/var/www/html/vendor/bin" "$BASHRC" || echo "export PATH=\$PATH:/var/www/html/vendor/bin" >> "$BASHRC"

# Install Apache
echo "Installing Apache..."
sudo apt-get install -y apache2
sudo systemctl enable --now apache2
sudo a2enmod rewrite

sudo apt-get purge -y libapache2-mod-php8.2 || true

# Set ServerName to suppress AH00558 warning
APACHE_CONF="/etc/apache2/apache2.conf"
grep -q "^ServerName" "$APACHE_CONF" || echo "ServerName ubuntu-wamp.local" | sudo tee -a "$APACHE_CONF" > /dev/null

# Install PHP 8.2 and required extensions
sudo apt-get install -y \
    php8.2 \
    php8.2-fpm \
    php8.2-mysql \
    php8.2-curl \
    php8.2-cli \
    php8.2-common \
    php8.2-zip \
    php8.2-gd \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-bcmath \
    php8.2-opcache \
    php8.2-intl \
    php8.2-soap \
    php8.2-xdebug \
    -o Dpkg::Options::="--force-confdef" \
    -o Dpkg::Options::="--force-confold"

sudo a2enmod proxy_fcgi setenvif
sudo a2enconf php8.2-fpm
sudo systemctl enable --now php8.2-fpm

# Set PHP 8.2 as default
sudo update-alternatives --set php /usr/bin/php8.2

# Configure PHP-FPM pool settings and enable OPcache
echo "Configuring PHP-FPM pool and OPcache..."
PHP_FPM_CONF="/etc/php/8.2/fpm/pool.d/www.conf"

# Set OPcache settings if not already present
if ! grep -q "opcache.enable" "$PHP_FPM_CONF"; then
    sudo tee -a "$PHP_FPM_CONF" > /dev/null <<'EOL'

; OPcache settings for PHP-FPM
php_admin_value[opcache.enable] = 1
php_admin_value[opcache.memory_consumption] = 128
php_admin_value[opcache.interned_strings_buffer] = 16
php_admin_value[opcache.max_accelerated_files] = 10000
php_admin_value[opcache.validate_timestamps] = 1
EOL
fi

# Replace pool management settings
sudo sed -i 's/^pm = .*/pm = dynamic/' "$PHP_FPM_CONF"
sudo sed -i 's/^pm.max_children = .*/pm.max_children = 10/' "$PHP_FPM_CONF"
sudo sed -i 's/^pm.start_servers = .*/pm.start_servers = 2/' "$PHP_FPM_CONF"
sudo sed -i 's/^pm.min_spare_servers = .*/pm.min_spare_servers = 2/' "$PHP_FPM_CONF"
sudo sed -i 's/^pm.max_spare_servers = .*/pm.max_spare_servers = 4/' "$PHP_FPM_CONF"

# Restart PHP-FPM to apply changes
sudo systemctl restart php8.2-fpm

# Preconfigure MySQL root password
echo "mysql-server mysql-server/root_password password root" | sudo debconf-set-selections
echo "mysql-server mysql-server/root_password_again password root" | sudo debconf-set-selections

# Install MySQL
echo "Installing MySQL..."
sudo apt-get install -y mysql-server
sudo systemctl enable --now mysql

# Wait until MySQL is active
sleep 5
until systemctl is-active --quiet mysql; do
    sleep 2
done

# Modify MySQL bind-address
MYSQL_CNF="/etc/mysql/mysql.conf.d/mysqld.cnf"
if [ -f "$MYSQL_CNF" ]; then
    echo "Modifying MySQL bind-address..."
    if grep -q "^bind-address" "$MYSQL_CNF"; then
        sudo sed -i 's/^bind-address\s*=.*/bind-address = 0.0.0.0/' "$MYSQL_CNF"
    else
        echo "bind-address = 0.0.0.0" | sudo tee -a "$MYSQL_CNF" > /dev/null
    fi
    sleep 2
    sudo systemctl restart mysql
    sudo mysqladmin ping --silent || { echo "MySQL not running!" ; exit 1; }
else
    echo "Error: MySQL configuration file not found!"
fi

# Configure MySQL users and database
mysql -uroot -proot <<EOF
CREATE USER IF NOT EXISTS 'root'@'%' IDENTIFIED WITH mysql_native_password BY 'root';
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'root';
ALTER USER 'root'@'%' IDENTIFIED WITH mysql_native_password BY 'root';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;

CREATE DATABASE IF NOT EXISTS hd2025
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_520_ci;
EOF

echo "Database 'hd2025' has been created (or already exists) with utf8mb4 charset and utf8mb4_unicode_520_ci collation."

# Adjust web directory permissions
echo "Adjusting web directory permissions (excluding node_modules)..."
sudo find /var/www/html \( -path "*/node_modules" -o -path "*/node_modules/*" \) -prune -o -exec chown www-data:www-data {} +
sudo find /var/www/html \( -path "*/node_modules" -o -path "*/node_modules/*" \) -prune -o -exec chmod 755 {} +

# Copy Apache site config if exists
if [ -f /home/vagrant/config/default.conf ]; then
    sudo cp /home/vagrant/config/default.conf /etc/apache2/sites-available/999-default.conf
    sudo a2ensite 999-default.conf
fi

# Copy php.ini to CLI and FPM only
if [ -f /home/vagrant/config/php.ini ]; then
    sudo cp /home/vagrant/config/php.ini /etc/php/8.2/fpm/conf.d/99-custom.ini
    sudo cp /home/vagrant/config/php.ini /etc/php/8.2/cli/conf.d/99-custom.ini
    sudo chmod 644 /etc/php/8.2/fpm/conf.d/99-custom.ini /etc/php/8.2/cli/conf.d/99-custom.ini
fi

# Download and extract phpMyAdmin
echo "Downloading phpMyAdmin..."
wget -q https://files.phpmyadmin.net/phpMyAdmin/5.2.2/phpMyAdmin-5.2.2-all-languages.tar.gz -O /tmp/phpmyadmin.tar.gz
sudo tar -xzf /tmp/phpmyadmin.tar.gz -C /var/www/
sudo mv /var/www/phpMyAdmin-5.2.2-all-languages /var/www/phpmyadmin

# Set permissions
sudo chown -R www-data:www-data /var/www/phpmyadmin
sudo chmod -R 755 /var/www/phpmyadmin

# Copy phpMyAdmin config if exists
if [ -f /home/vagrant/config/config.inc.php ]; then
    sudo cp /home/vagrant/config/config.inc.php /var/www/phpmyadmin/config.inc.php
    sudo chmod 644 /var/www/phpmyadmin/config.inc.php
fi

# Setup phpMyAdmin VirtualHost
if [ -f /home/vagrant/config/phpmyadmin.conf ]; then
    sudo cp /home/vagrant/config/phpmyadmin.conf /etc/apache2/sites-available/phpmyadmin.conf
    sudo a2ensite phpmyadmin.conf
fi

# Ensure Apache listens on port 8081
APACHE_PORTS="/etc/apache2/ports.conf"
grep -q "Listen 8081" "$APACHE_PORTS" || echo "Listen 8081" | sudo tee -a "$APACHE_PORTS" > /dev/null

# Restart Apache
sudo systemctl restart apache2

# Clean up
sudo apt-get clean

# Final messages
echo "Provisioning complete!"
echo "Homepage: http://localhost:8080"
echo "phpMyAdmin: http://localhost:8081"
echo "Remember to run 'source ~/.bashrc' inside the VM after 'vagrant ssh'"
