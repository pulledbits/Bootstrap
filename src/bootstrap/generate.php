<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Functional as F;
use function rikmeijer\Bootstrap\Configuration\path;

return configure(static function (array $configuration): void {
    $fp = $configuration['target']('wb');
    $write = F\partial_left('\\fwrite', $fp);
    $write('<?php declare(strict_types=1);' . PHP_EOL);
    \rikmeijer\Bootstrap\resource\generate($configuration['resources'], '', F\partial_left(static function (string $namespace, callable $write, string $resourcePath, string $groupNamespace) {
        $f = PHP::extractGlobalFunctionFromFile($resourcePath, $functionNS);

        if ($functionNS !== null) {
            $resourceNS = $functionNS;
        } else {
            $resourceNS = $namespace;
            if ($groupNamespace !== '') {
                $resourceNS .= '\\' . $groupNamespace;
            }
        }

        $write(PHP_EOL . 'namespace ' . $resourceNS . ' { ');
        $write($f('\\rikmeijer\Bootstrap\resource\open(' . PHP::export($resourcePath) . ', true)(...func_get_args());'));
        $write('}' . PHP_EOL);
    }, $configuration['namespace'], $write));
    fclose($fp);
}, [
    'resources' => types\path(path() . DIRECTORY_SEPARATOR . 'bootstrap'),
    'target'    => \rikmeijer\Bootstrap\types\file(path() . DIRECTORY_SEPARATOR . 'bootstrap.php'),
    'namespace' => types\string(__NAMESPACE__ . '\\' . basename(path()))
], 'BOOTSTRAP');