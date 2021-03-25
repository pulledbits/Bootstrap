<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use function rikmeijer\Bootstrap\Configuration\path;

return configure(static function (array $configuration): void {
    $fp = $configuration['target']('wb');
    \rikmeijer\Bootstrap\resource\generate(static fn() => '$resourcePath, true', $fp, $configuration['resources'], $configuration['namespace']);
    fclose($fp);
}, [
    'resources' => types\path(path() . DIRECTORY_SEPARATOR . 'bootstrap'),
    'target'    => \rikmeijer\Bootstrap\types\file(path() . DIRECTORY_SEPARATOR . 'bootstrap.php'),
    'namespace' => types\string(__NAMESPACE__ . '\\' . basename(path()))
], 'BOOTSTRAP');