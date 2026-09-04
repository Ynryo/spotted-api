#!/usr/bin/env bash
set -e

echo "🚀 Début du déploiement de spotted-api..."

# 1. Récupérer le dernier code source
echo "📥 Récupération des dernières modifications Git..."
git pull origin main

# 2. Reconstruire et relancer les conteneurs Docker
echo "🐳 Reconstruction des images et redémarrage des conteneurs..."
docker compose build
docker compose up -d --remove-orphans

# 3. Installer les dépendances Composer en mode production
echo "📦 Installation des dépendances Composer..."
docker compose exec -T php composer install --no-dev --optimize-autoloader

# 4. Installer les assets de l'API (Swagger UI)
echo "🎨 Installation des assets publics..."
docker compose exec -T php php bin/console assets:install public

# 5. Vider et réchauffer le cache Symfony
echo "🧹 Nettoyage du cache de production..."
docker compose exec -T php php bin/console cache:clear --env=prod

echo "✅ Déploiement terminé avec succès sur https://api.spotted.ynryo.fr !"
