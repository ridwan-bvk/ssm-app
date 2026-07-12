<?php

namespace App\Filament\Concerns;

/**
 * Consumers must declare `protected static ?string $permission = '...';`
 * themselves — the property is deliberately not declared here, since PHP
 * treats a class overriding a trait-declared property's default value as
 * an incompatible redeclaration (fatal error), even when the type matches.
 */
trait AuthorizesViaPermission
{
    public static function canViewAny(): bool
    {
        return static::userHasPermission();
    }

    public static function canAccess(): bool
    {
        return static::userHasPermission();
    }

    protected static function userHasPermission(): bool
    {
        return static::$permission !== null
            && (auth()->user()?->can(static::$permission) ?? false);
    }
}
