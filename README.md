# Pingix Deploy

Déploie une app Laravel sur n'importe quel VPS (Ubuntu 24.04), sans coupure, en une commande.

```bash
php artisan deploy:provision   # une fois : installe et configure le serveur
php artisan deploy             # à chaque mise en prod
php artisan deploy:rollback    # retour instantané à la version précédente
```

## Comment ça marche

Les commandes tournent sur ton PC et envoient des scripts bash au serveur via SSH.

```
/var/www/monapp/
├── releases/20261112093000/   ← chaque déploiement = un nouveau dossier
├── shared/.env                ← commun à toutes les releases
├── shared/storage/
└── current → releases/…       ← symlink, Nginx pointe ici
```

La nouvelle version est construite à côté de celle en ligne, puis `current` bascule
d'un coup. Si une étape échoue avant la bascule, la release est supprimée et le site
n'est pas touché.

## Installation (en développement local du package)

Dans le `composer.json` de ton app Laravel :

```json
"repositories": [
    { "type": "path", "url": "../pingix-deploy" }
]
```

```bash
composer require pingix/deploy:@dev --dev
php artisan vendor:publish --tag=deploy-config
```

## Configuration (.env de ton PC)

```dotenv
DEPLOY_APP=monapp
DEPLOY_HOST=51.38.12.34
DEPLOY_PROVISION_USER=ubuntu     # "root" ou "ubuntu" (OVH)
DEPLOY_DOMAIN=monapp.fr          # le DNS doit pointer vers le serveur avant le provisioning
DEPLOY_SSL_EMAIL=toi@exemple.fr
```

Le dépôt Git est détecté automatiquement (`git remote get-url origin`).

### Accès Git du serveur

- `DEPLOY_GIT_AUTH=agent` (défaut) : le serveur clone avec **ta** clé via `ssh -A`.
  Rien à configurer, il faut juste que ta clé soit chargée : `ssh-add ~/.ssh/id_ed25519`.
- `DEPLOY_GIT_AUTH=deploy-key` : le serveur génère sa propre clé, affichée à la fin
  du provisioning, à coller dans GitHub > Settings > Deploy keys.

## Options utiles

```bash
php artisan deploy --dry-run           # affiche le script sans l'exécuter
php artisan deploy --branch=staging
php artisan deploy:rollback 20261112093000
php artisan vendor:publish --tag=deploy-stubs   # personnaliser nginx, supervisor, scripts
```

## Déploiement automatique (GitHub Actions)

```yaml
name: Deploy
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.4' }
      - uses: webfactory/ssh-agent@v0.9.0
        with: { ssh-private-key: "${{ secrets.DEPLOY_SSH_KEY }}" }
      - run: composer install --no-interaction
      - run: php artisan deploy
        env:
          DEPLOY_HOST: ${{ secrets.DEPLOY_HOST }}
          DEPLOY_GIT_AUTH: deploy-key
```

## Limites de la v1

- Ubuntu 24.04, PostgreSQL uniquement
- Un seul serveur par app
- Le rollback n'annule pas les migrations

## Roadmap

- [ ] `deploy:logs`, `deploy:ssh`, `deploy:env` (éditer le .env distant)
- [ ] MySQL, FrankenPHP, Reverb
- [ ] Environnements de preview par pull request
- [ ] Multi-serveurs
- [ ] Dashboard web

## Licence

MIT
