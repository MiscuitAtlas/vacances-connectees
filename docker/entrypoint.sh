#!/bin/sh
# Démarrage de la démo en ligne : base modèle du jour, puis Apache.
set -e
cd /var/www/html

# Adresse publique fournie par Render, utilisée dans les liens des emails.
if [ -z "${APP_URL:-}" ] && [ -n "${RENDER_EXTERNAL_URL:-}" ]; then
    export APP_URL="$RENDER_EXTERNAL_URL"
fi

php bin/demo.php

# La commande précédente tourne en root : Apache doit pouvoir écrire les copies des visiteurs.
chown -R www-data:www-data var

exec apache2-foreground
