<?php

namespace App\Helpers;

class Helper
{
    protected static array $pageConfig = [];

    /**
     * Update the page configuration (title, breadcrumb, etc.)
     *
     * @param array $config
     * @return void
     */
    public static function updatePageConfig(array $config): void
    {
        static::$pageConfig = array_merge(static::$pageConfig, $config);
    }

    /**
     * Get page config value.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public static function getPageConfig(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return static::$pageConfig;
        }

        return static::$pageConfig[$key] ?? $default;
    }

    /**
     * Reset page config (called per-request in Octane).
     */
    public static function resetPageConfig(): void
    {
        static::$pageConfig = [];
    }
}
