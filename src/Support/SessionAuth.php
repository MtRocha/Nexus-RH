<?php

declare(strict_types=1);

namespace NexusRH\Support;

final class SessionAuth
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function setCurrentUser(array $user): void
    {
        self::start();
        $_SESSION['auth_user'] = $user;
        unset($_SESSION['auth_pending']);
    }

    public static function currentUser(): ?array
    {
        self::start();
        return isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user']) ? $_SESSION['auth_user'] : null;
    }

    public static function setPendingUser(array $user): void
    {
        self::start();
        $_SESSION['auth_pending'] = [
            'user' => $user,
            'expires_at' => time() + 600,
        ];
    }

    public static function pendingUser(): ?array
    {
        self::start();

        if (!isset($_SESSION['auth_pending']) || !is_array($_SESSION['auth_pending'])) {
            return null;
        }

        $pending = $_SESSION['auth_pending'];

        if (($pending['expires_at'] ?? 0) < time()) {
            unset($_SESSION['auth_pending']);
            return null;
        }

        return is_array($pending['user'] ?? null) ? $pending['user'] : null;
    }

    public static function clear(): void
    {
        self::start();
        unset($_SESSION['auth_user'], $_SESSION['auth_pending']);
    }
}