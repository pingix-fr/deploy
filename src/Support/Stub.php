<?php

namespace Pingix\Deploy\Support;

use RuntimeException;

/**
 * Rendu des fichiers stubs/*.stub : remplace les {{ variable }}.
 * Si le projet a publié ses stubs (stubs/deploy/), ils sont prioritaires.
 */
final class Stub
{
    public static function render(string $name, array $vars): string
    {
        $content = file_get_contents(self::path($name));

        return preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', function (array $m) use ($vars, $name) {
            if (! array_key_exists($m[1], $vars)) {
                throw new RuntimeException("Variable « {$m[1]} » manquante pour le stub {$name}.");
            }

            return (string) $vars[$m[1]];
        }, $content);
    }

    private static function path(string $name): string
    {
        if (function_exists('base_path')) {
            $custom = base_path('stubs/deploy/'.$name);
            if (is_file($custom)) {
                return $custom;
            }
        }

        $default = dirname(__DIR__, 2).'/stubs/'.$name;

        if (! is_file($default)) {
            throw new RuntimeException("Stub introuvable : {$name}");
        }

        return $default;
    }
}
