<?php

namespace Pingix\Deploy\Ssh;

use Symfony\Component\Process\Process;

/**
 * Envoie un script bash à un serveur via la commande `ssh` du système.
 * On profite ainsi des clés et de la config SSH (~/.ssh/config) de l'utilisateur.
 */
final class Connection
{
    public function __construct(
        private readonly string $host,
        private readonly string $user,
        private readonly int $port = 22,
        private readonly bool $forwardAgent = false,
    ) {}

    /**
     * @param  bool  $sudo  exécute le script en root via sudo (provisioning avec l'utilisateur "ubuntu")
     * @param  callable(string):void|null  $output  reçoit la sortie en direct
     */
    public function run(string $script, bool $sudo = false, ?callable $output = null): void
    {
        $command = [
            'ssh',
            '-p', (string) $this->port,
            '-o', 'BatchMode=yes',                    // jamais de prompt de mot de passe
            '-o', 'StrictHostKeyChecking=accept-new', // accepte un nouveau serveur, refuse une clé qui change
        ];

        if ($this->forwardAgent) {
            $command[] = '-A';
        }

        $command[] = "{$this->user}@{$this->host}";
        $command[] = $sudo ? 'sudo bash -se' : 'bash -se';

        $process = new Process($command);
        $process->setInput("set -eo pipefail\n".$script);
        $process->setTimeout(1800);

        // mustRun lève ProcessFailedException si le script échoue (set -e)
        $process->mustRun(function (string $type, string $buffer) use ($output) {
            if ($output) {
                $output($buffer);
            }
        });
    }

    public function target(): string
    {
        return "{$this->user}@{$this->host}:{$this->port}";
    }
}
