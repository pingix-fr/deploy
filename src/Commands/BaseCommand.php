<?php

namespace Pingix\Deploy\Commands;

use Illuminate\Console\Command;
use Pingix\Deploy\Ssh\Connection;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

abstract class BaseCommand extends Command
{
    /** Config validée + valeurs dérivées (path, db…). */
    protected function settings(): array
    {
        $c = config('deploy');

        if (empty($c['host'])) {
            throw new RuntimeException('DEPLOY_HOST manquant dans ton .env.');
        }

        if (! preg_match('/^[a-z0-9][a-z0-9-]*$/', (string) $c['app'])) {
            throw new RuntimeException('DEPLOY_APP doit contenir uniquement des minuscules, chiffres et tirets.');
        }

        if (! in_array($c['git_auth'], ['agent', 'deploy-key'], true)) {
            throw new RuntimeException("DEPLOY_GIT_AUTH doit valoir 'agent' ou 'deploy-key'.");
        }

        $c['path'] = '/var/www/'.$c['app'];
        $c['db'] = str_replace('-', '_', $c['app']);
        $c['repository'] = $this->toSshUrl($c['repository'] ?: $this->detectRepository());

        return $c;
    }

    protected function connection(array $c, string $user, bool $forwardAgent = false): Connection
    {
        return new Connection($c['host'], $user, $c['port'], $forwardAgent);
    }

    /** Exécute le script (ou l'affiche avec --dry-run) et gère l'erreur proprement. */
    protected function execute(Connection $connection, string $script, bool $sudo = false): int
    {
        if ($this->option('dry-run')) {
            $this->line($script);

            return self::SUCCESS;
        }

        $this->components->info('Connexion à '.$connection->target());

        try {
            $connection->run($script, $sudo, fn (string $out) => $this->output->write($out));
        } catch (ProcessFailedException) {
            $this->components->error('Échec : voir la sortie ci-dessus.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /** Commandes pour recharger les workers après une bascule de release. */
    protected function restartWorkers(array $c): string
    {
        $restart = $c['horizon'] ? 'php artisan horizon:terminate' : 'php artisan queue:restart';

        // Au premier déploiement, Supervisor a abandonné (pas encore de code) : on relance.
        return $restart."\n"
            ."sudo /usr/bin/supervisorctl start '{$c['app']}-worker:*' >/dev/null 2>&1 || true";
    }

    private function detectRepository(): string
    {
        $process = new Process(['git', 'remote', 'get-url', 'origin'], base_path());
        $process->run();

        $url = trim($process->getOutput());

        if (! $process->isSuccessful() || $url === '') {
            throw new RuntimeException('Impossible de détecter le dépôt Git : définis DEPLOY_REPOSITORY.');
        }

        return $url;
    }

    /** https://github.com/theo/app.git → git@github.com:theo/app.git (nécessaire pour l'agent SSH). */
    private function toSshUrl(string $url): string
    {
        if (preg_match('#^https://(github\.com|gitlab\.com|bitbucket\.org)/(.+)$#', $url, $m)) {
            return "git@{$m[1]}:{$m[2]}";
        }

        return $url;
    }
}
