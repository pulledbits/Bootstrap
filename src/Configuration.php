<?php declare(strict_types=1);


namespace rikmeijer\Bootstrap;


class Configuration
{
    private static array $configs = [];

    public static function open(string $root, string $section): array
    {
        if (isset(self::$configs[$root]) === false) {
            self::$configs[$root] = array_merge_recursive_distinct(self::openDefaults($root), self::openLocal($root));
        }
        if (array_key_exists($section, self::$configs[$root]) === false) {
            return [];
        }
        return self::$configs[$root][$section];
    }

    private static function openDefaults(string $root): array
    {
        return self::include($root . DIRECTORY_SEPARATOR . 'config.defaults.php');
    }

    private static function include(string $path): array
    {
        if (file_exists($path) === false) {
            return [];
        }
        $config = (include $path);
        return is_array($config) ? $config : [];
    }

    private static function openLocal(string $root): array
    {
        return self::include($root . DIRECTORY_SEPARATOR . 'config.php');
    }

}