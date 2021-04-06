<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\configuration;

use function Functional\partial_left;

function generate(string $from, string $namespace): void
{
    $fopen = partial_left('\\fopen', $from . '.php');
    \rikmeijer\Bootstrap\resource\generate($fopen, $from, $namespace);
}