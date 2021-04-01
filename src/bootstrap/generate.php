<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use function rikmeijer\Bootstrap\configuration\path;
use function rikmeijer\Bootstrap\types\boolean;

return configure(static function (array $configuration): void {
    \rikmeijer\Bootstrap\resource\generate($configuration['use-cache'], $configuration['target'], $configuration['resources'], $configuration['namespace']);
}, [
    'use-cache' => boolean(true),
    'resources' => types\path(path() . DIRECTORY_SEPARATOR . 'bootstrap'),
    'target'    => \rikmeijer\Bootstrap\types\file(path() . DIRECTORY_SEPARATOR . 'bootstrap.php'),
    'namespace' => types\string(__NAMESPACE__ . '\\' . basename(path()))
], 'BOOTSTRAP');