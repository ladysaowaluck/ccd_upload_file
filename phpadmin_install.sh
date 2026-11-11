#!/bin/bash
source /var/www/html/.env


PHP_VERSION=5.2.0
PHP_FILE=phpMyAdmin-5.2.0-all-languages
PHPMYADMIN_NEW_NAME=$PHPMYADMIN_NEW_NAME
PHPMYADMIN_SECRET=$PHPMYADMIN_SECRET
DB_HOST=$DB_HOST

wget https://files.phpmyadmin.net/phpMyAdmin/$PHP_VERSION/$PHP_FILE.zip
mv /var/www/html/$PHP_FILE.zip /var/www/html/public/
cd /var/www/html/public/
unzip $PHP_FILE.zip
mv $PHP_FILE $PHPMYADMIN_NEW_NAME

cd /var/www/html/public/$PHPMYADMIN_NEW_NAME
mkdir -p /var/www/html/public/$PHPMYADMIN_NEW_NAME/tmp
chmod 755 /var/www/html/public/$PHPMYADMIN_NEW_NAME/tmp

sed -e "s/\$cfg\['blowfish_secret'\] = ''/\$cfg\['blowfish_secret'\] = '$PHPMYADMIN_SECRET'/" /var/www/html/public/$PHPMYADMIN_NEW_NAME/config.sample.inc.php > /var/www/html/public/$PHPMYADMIN_NEW_NAME/config.inc.php_1

sed -e "s/\$cfg\['Servers'\]\[\$i]\['host'] = 'localhost'/\$cfg\['Servers'\]\[\$i]\['host'] = '$DB_HOST'/" /var/www/html/public/$PHPMYADMIN_NEW_NAME/config.inc.php_1 > /var/www/html/public/$PHPMYADMIN_NEW_NAME/config.inc.php_2
sed -e "s/\$cfg\['Servers'\]\[\$i]\['host'] = 'localhost'/\$cfg\['Servers'\]\[\$i]\['host'] = '$DB_HOST'/" /var/www/html/public/$PHPMYADMIN_NEW_NAME/config.inc.php_2 > /var/www/html/public/$PHPMYADMIN_NEW_NAME/config.inc.php_3
sed -e "s/\$cfg\['Servers'\]\[\$i]\['auth_type'] = 'cookie'/\$cfg\['Servers'\]\[\$i]\['auth_type'] = 'http'/" /var/www/html/public/$PHPMYADMIN_NEW_NAME/config.inc.php_3 > /var/www/html/public/$PHPMYADMIN_NEW_NAME/config.inc.php

