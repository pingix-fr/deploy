<?php

namespace Pingix\Deploy;

use Illuminate\Support\ServiceProvider;
use Pingix\Deploy\Commands\DeployCommand;
use Pingix\Deploy\Commands\ProvisionCommand;
use Pingix\Deploy\Commands\RollbackCommand;

class DeployServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/deploy.php', 'deploy');
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/deploy.php' => config_path('deploy.php'),
        ], 'deploy-config');

        // Permet de personnaliser nginx, supervisor, les scripts… dans stubs/deploy/
        $this->publishes([
            __DIR__.'/../stubs' => base_path('stubs/deploy'),
        ], 'deploy-stubs');

        $this->commands([
            ProvisionCommand::class,
            DeployCommand::class,
            RollbackCommand::class,
        ]);
    }
}
