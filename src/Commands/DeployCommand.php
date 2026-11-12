<?php

namespace Pingix\Deploy\Commands;

use Pingix\Deploy\Support\Stub;

class DeployCommand extends BaseCommand
{
    protected $signature = 'deploy
        {--branch= : Branche à déployer (par défaut : DEPLOY_BRANCH)}
        {--dry-run : Afficher le script sans l\'exécuter}';

    protected $description = 'Déploie l\'application sur le serveur, sans coupure';

    public function handle(): int
    {
        $c = $this->settings();
        $useAgent = $c['git_auth'] === 'agent';

        $script = Stub::render('deploy.sh.stub', [
            'path' => $c['path'],
            'release' => date('YmdHis'),
            'branch' => $this->option('branch') ?: $c['branch'],
            'repository' => $c['repository'],
            'php' => $c['php'],
            'keep_from' => max(1, $c['keep_releases']) + 1,
            'agent_check' => $useAgent && ! str_starts_with($c['repository'], 'https://')
                ? 'ssh-add -l >/dev/null 2>&1 || { echo "✗ Aucune clé SSH transmise. Sur ton PC : ssh-add ~/.ssh/id_ed25519"; exit 1; }'
                : ':',
            'before_activate' => $this->hooks($c['hooks']['before_activate'] ?? []),
            'after_activate' => $this->hooks($c['hooks']['after_activate'] ?? []),
            'restart_workers' => $this->restartWorkers($c),
        ]);

        return $this->execute($this->connection($c, $c['user'], forwardAgent: $useAgent), $script);
    }

    private function hooks(array $commands): string
    {
        return $commands ? implode("\n", $commands) : ':';
    }
}
