#!/bin/sh
set -eu

cd /var/www || exit 1

# Ensure writable dirs for Laravel session/cache/files.
#
# Security note:
# - This is intended for local development Docker only.
# - We try to keep permissions minimal (www-data + group writable).
# - On bind mounts (macOS/Windows), chown may fail; we then fall back to chmod.
fix_perms() {
  dir="$1"
  if [ -d "$dir" ]; then
    chown -R www-data:www-data "$dir" 2>/dev/null || true
    chmod -R ug+rwX "$dir" 2>/dev/null || true

    # Try to avoid world-writable permissions.
    chmod -R o-w "$dir" 2>/dev/null || true

    # Last resort: make writable for everyone on bind mounts.
    # Avoid using this approach in production.
    if [ ! -w "$dir" ]; then
      chmod -R a+rwX "$dir" 2>/dev/null || true
    fi
  fi
}

fix_perms storage
fix_perms bootstrap/cache

# Create storage symlink for public access (safe to rerun).
# We intentionally do this without artisan so it works even before composer install.
if [ ! -e public/storage ]; then
  mkdir -p public storage/app/public
  ln -sfn /var/www/storage/app/public /var/www/public/storage 2>/dev/null || true
fi

exec "$@"
