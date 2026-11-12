<?php

namespace Pingix\Deploy\Commands;

use Pingix\Deploy\Support\Stub;

class RollbackCommand extends BaseCommand
{
    protected $signature = 'deploy:rollback
        {release? : ID de la release cible (par défaut : la précédente)}
        {--dry-run : Afficher le script sans l\'exécuter}';

    protected $description = 'Revient instantanément à une release précédente';

    public function handle(): int
    {
        $c = $this->settings();
        $target = (string) $this->argument('release');

        if ($target !== '' && ! ctype_digit($target)) {
            $this->components->error('ID de release invalide (format : 20261112093000).');

            return self::FAILURE;
        }

        $script = Stub::render('rollback.sh.stub', [
            'path' => $c['path'],
            'php' => $c['php'],
            'target' => $target,
            'restart_workers' => $this->restartWorkers($c),
        ]);

        return $this->execute($this->connection($c, $c['user']), $script);
    }
}
