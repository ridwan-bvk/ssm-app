<?php

namespace App\Filament\Concerns;

/**
 * Consumers must declare `protected static ?string $requiredRole = '...';`
 * themselves — the property is deliberately not declared here, since PHP
 * treats a class overriding a trait-declared property's default value as
 * an incompatible redeclaration (fatal error), even when the type matches.
 */
trait AuthorizesViaRole
{
    public static function canViewAny(): bool
    {
        return static::userHasRole();
    }

    public static function canAccess(): bool
    {
        return static::userHasRole();
    }

    protected static function userHasRole(): bool
    {
        return static::$requiredRole !== null
            && (auth()->user()?->hasRole(static::$requiredRole) ?? false);
    }
}
