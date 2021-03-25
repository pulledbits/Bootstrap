<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Functional as F;
use function rikmeijer\Bootstrap\Configuration\path;

return configure(static function (array $configuration): void {
    $fp = $configuration['target']('wb');
    $write = F\partial_left('\\fwrite', $fp);
    $write('<?php declare(strict_types=1);' . PHP_EOL);
    \rikmeijer\Bootstrap\resource\generate(F\partial_left(static function (callable $write, callable $functionGenerator, string $resourcePath) {
        $write($functionGenerator('\\rikmeijer\Bootstrap\resource\\open(' . PHP::export($resourcePath) . ', true)'));
    }, $write))($configuration['resources'], $configuration['namespace']);
    fclose($fp);
}, [
    'resources' => types\path(path() . DIRECTORY_SEPARATOR . 'bootstrap'),
    'target'    => \rikmeijer\Bootstrap\types\file(path() . DIRECTORY_SEPARATOR . 'bootstrap.php'),
    'namespace' => types\string(__NAMESPACE__ . '\\' . basename(path()))
], 'BOOTSTRAP');