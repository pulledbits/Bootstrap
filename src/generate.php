<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Functional as F;

function generate(): void
{
    $configurationPath = Configuration\path();
    $fp = fopen($configurationPath . DIRECTORY_SEPARATOR . 'bootstrap.php', 'wb');
    $write = F\partial_left('\\fwrite', $fp);
    $write('<?php declare(strict_types=1);' . PHP_EOL);
    Resource::generate(resources(), F\partial_left(configure(static function (array $configuration, callable $write, string $resourcePath, string $groupNamespace) {
        $f = PHP::extractGlobalFunctionFromFile($resourcePath, $functionNS);

        if ($functionNS !== null) {
            $resourceNS = $functionNS;
        } else {
            $resourceNS = $configuration['namespace'];
            if ($groupNamespace !== '') {
                $resourceNS .= '\\' . $groupNamespace;
            }
        }

        $write(PHP_EOL . 'namespace ' . $resourceNS . ' { ');
        $write($f('\\' . Resource::class . '::open(' . PHP::export($resourcePath) . ')(...func_get_args());'));
        $write('}' . PHP_EOL);
    }, [
        'namespace' => types\string(__NAMESPACE__ . '\\' . basename($configurationPath))
    ], 'BOOTSTRAP'), $write));
    fclose($fp);
}