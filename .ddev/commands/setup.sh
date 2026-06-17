#!/bin/bash

if [ -f .ddev/wordpress/wp-load.php ]; then
  echo "WordPress already installed"
  exit;
fi

echo "Installing WordPress..."

wp core download

wp core install \
  --title="${WP_TITLE}" \
  --admin_user="${ADMIN_USER}" \
  --admin_password="${ADMIN_PASS}" \
  --url="${DDEV_PRIMARY_URL}" \
  --admin_email="admin@example.com" \
  --skip-email

wp rewrite flush --hard

wp plugin activate one-click-multisite

wp plugin delete hello
wp plugin delete akismet
