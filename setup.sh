#!/bin/sh
# Dev environment setup for Pudimbooru (Shimmie2 fork).
#
# Installs PHP dependencies and prepares a local SQLite-backed instance so
# the app and test suite can run without a separate database server.
set -eu

cd "$(dirname "$0")"

echo "==> Checking PHP version"
php -v | head -1

echo "==> Installing Composer dependencies"
composer install --no-interaction --prefer-dist

echo "==> Creating data directories"
mkdir -p data/config data/cache

DSN="sqlite:data/shimmie.dev.sqlite"

if [ ! -f data/config/shimmie.conf.php ]; then
  echo "==> No install found yet."
  echo "    On first HTTP request (or CLI run), Shimmie2 will run its"
  echo "    installer automatically using INSTALL_DSN below."
fi

cat <<EOF

Setup complete!

To run the app locally:
  INSTALL_DSN="$DSN" php -S localhost:8000

  (or use Docker: composer docker-run)

To run tests:
  TEST_DSN="sqlite::memory:" composer test

To run checks (format + static analysis + tests), same as CI:
  composer check
EOF
