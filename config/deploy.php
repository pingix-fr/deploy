<?php

return [

    // Nom court de l'app (minuscules, chiffres, tirets). Sert pour le dossier
    // /var/www/{app}, la base de données, Supervisor, le cron…
    'app' => env('DEPLOY_APP', 'monapp'),

    // ── Serveur ─────────────────────────────────────────────────────────
    'host' => env('DEPLOY_HOST'),                              // ex: 51.38.12.34
    'port' => (int) env('DEPLOY_PORT', 22),
    'user' => env('DEPLOY_USER', 'deploy'),                    // créé par deploy:provision
    'provision_user' => env('DEPLOY_PROVISION_USER', 'root'),  // 'ubuntu' sur un VPS OVH

    // ── Code ────────────────────────────────────────────────────────────
    'repository' => env('DEPLOY_REPOSITORY'),   // null = remote "origin" du projet local
    'branch' => env('DEPLOY_BRANCH', 'main'),

    // 'agent'      : le serveur clone avec TA clé GitHub, transmise via ssh -A (rien à configurer)
    // 'deploy-key' : le serveur a sa propre clé, à ajouter dans GitHub > Settings > Deploy keys
    'git_auth' => env('DEPLOY_GIT_AUTH', 'agent'),

    // ── Web ─────────────────────────────────────────────────────────────
    'domain' => env('DEPLOY_DOMAIN'),           // null = accès par IP, sans SSL
    'ssl_email' => env('DEPLOY_SSL_EMAIL'),     // requis pour Let's Encrypt
    'php' => env('DEPLOY_PHP', '8.4'),
    'node' => env('DEPLOY_NODE', '22'),

    // ── Process ─────────────────────────────────────────────────────────
    'workers' => (int) env('DEPLOY_WORKERS', 2),
    'horizon' => (bool) env('DEPLOY_HORIZON', false),

    // Nombre de releases gardées sur le serveur (pour rollback)
    'keep_releases' => (int) env('DEPLOY_KEEP', 5),

    // Commandes shell en plus, exécutées dans le dossier de la release
    'hooks' => [
        'before_activate' => [],   // ex: ['php artisan db:seed --class=ProdSeeder --force']
        'after_activate' => [],
    ],

];
