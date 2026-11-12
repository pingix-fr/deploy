<?php

namespace Pingix\Deploy\Commands;

use Illuminate\Support\Str;
use Pingix\Deploy\Support\Stub;

class ProvisionCommand extends BaseCommand
{
    protected $signature = 'deploy:provision
        {--no-ssl : Ne pas demander de certificat Let\'s Encrypt}
        {--dry-run : Afficher le script sans l\'exécuter}
        {--force : Ne pas demander de confirmation}';

    protected $description = 'Prépare un serveur neuf (PHP, Nginx, Postgres, Redis, Supervisor, SSL…)';

    public function handle(): int
    {
        $c = $this->settings();

        if (! $this->option('dry-run') && ! $this->option('force')
            && ! $this->confirm("Installer et configurer le serveur {$c['host']} pour « {$c['app']} » ?", true)) {
            return self::FAILURE;
        }

        $dbPassword = Str::random(40);
        $sslEnabled = $c['domain'] && $c['ssl_email'] && ! $this->option('no-ssl');
        $appUrl = $c['domain'] ? ($sslEnabled ? 'https://' : 'http://').$c['domain'] : 'http://'.$c['host'];

        $vars = [
            'app' => $c['app'],
            'path' => $c['path'],
            'user' => $c['user'],
            'php' => $c['php'],
            'node' => $c['node'],
            'db' => $c['db'],
            'db_password' => $dbPassword,
            'provision_home' => $c['provision_user'] === 'root' ? '/root' : '/home/'.$c['provision_user'],
            'deploy_key' => $c['git_auth'] === 'deploy-key' ? '1' : '0',
        ];

        $vars['env'] = Stub::render('env.stub', $vars + [
            'app_key' => 'base64:'.base64_encode(random_bytes(32)),
            'app_url' => $appUrl,
        ]);

        $vars['nginx'] = Stub::render('nginx.conf.stub', $vars + [
            'server_name' => $c['domain'] ?: '_',
        ]);

        $vars['supervisor'] = Stub::render('supervisor.conf.stub', $vars + [
            'command' => $c['horizon']
                ? 'php artisan horizon'
                : 'php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600',
            'numprocs' => $c['horizon'] ? 1 : max(1, $c['workers']),
        ]);

        $vars['ssl'] = $sslEnabled
            ? "certbot --nginx -d {$c['domain']} --non-interactive --agree-tos -m {$c['ssl_email']} --redirect"
            : 'echo "  (SSL ignoré : renseigne DEPLOY_DOMAIN et DEPLOY_SSL_EMAIL)"';

        $script = Stub::render('provision.sh.stub', $vars);

        $result = $this->execute(
            $this->connection($c, $c['provision_user']),
            $script,
            sudo: $c['provision_user'] !== 'root',
        );

        if ($result === self::SUCCESS && ! $this->option('dry-run')) {
            $this->components->info('Serveur prêt. Lance maintenant : php artisan deploy');
        }

        return $result;
    }
}
