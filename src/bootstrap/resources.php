<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

return static function (): array {
    return [
        Configuration\path() . DIRECTORY_SEPARATOR . 'bootstrap' => ''
    ];
};