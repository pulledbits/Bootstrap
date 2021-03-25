<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\types;

use Webmozart\PathUtil\Path;
use function Functional\partial_left;

return static function (?string $defaultValue = null): callable {
    return partial_left(static function (?string $defaultValue, mixed $value, callable $error) {
        if ($value === null) {
            $value = $defaultValue ?? $error('is not set and has no default value');
        }
        if (str_starts_with($value, 'php://')) {
            return $value;
        }
        if (Path::isRelative($value)) {
            return Path::join(\rikmeijer\Bootstrap\Configuration\path(), $value);
        }
        return $value;
    }, $defaultValue);
};