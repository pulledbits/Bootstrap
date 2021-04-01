<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\configuration;

use function Functional\partial_left;

return static function (string $from, string $namespace, bool $cached): void {
    $fopen = partial_left('\\fopen', $from . '.php');
    \rikmeijer\Bootstrap\resource\generate($cached, $fopen, $from, $namespace);
};