<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\configuration;

use Functional as F;
use Nette\PhpGenerator\GlobalFunction;
use rikmeijer\Bootstrap\PHP;
use rikmeijer\Bootstrap\Resource;

function generate(): void
{
    $fp = fopen(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'types.php', 'wb');
    $write = F\partial_left('\\fwrite', $fp);
    $write('<?php declare(strict_types=1);' . PHP_EOL);
    $write('namespace rikmeijer\Bootstrap\types;' . PHP_EOL);
    $write('use \\' . Resource::class . ';' . PHP_EOL);
    $write('function open(string $functionIdentifier) { return Resource::open(substr(__FILE__, 0, -4) . DIRECTORY_SEPARATOR . $functionIdentifier, false); }' . PHP_EOL);
    Resource::generate([dirname(__DIR__) => 'types'], F\partial_left(static function (callable $write, string $resourcePath) {
        $f = new GlobalFunction(basename($resourcePath, '.php'));
        $context = PHP::deductContextFromFile($resourcePath);
        if (array_key_exists('parameters', $context)) {
            F\each($context['parameters'], static function (array $contextParameter, int $index) use ($f) {
                if ($index === 0 && str_contains($contextParameter['name'], '$configuration')) {
                    return;
                }

                if ($contextParameter['variadic']) {
                    $f->setVariadic(true);
                }
                $parameter = $f->addParameter(substr($contextParameter['name'], 1));
                $parameter->setType($contextParameter['type']);
                $parameter->setNullable($contextParameter['nullable']);

                if (array_key_exists('default', $contextParameter)) {
                    $parameter->setDefaultValue($contextParameter['default']);
                }
            });
        }

        $returns = true;
        if (array_key_exists('returnType', $context)) {
            $returns = str_contains($context['returnType'], 'void') === false;
            $f->setReturnType($context['returnType']);
        }

        $body = '';
        if ($returns) {
            $body .= 'return ';
        }
        $body .= 'open(' . PHP::export(basename($resourcePath)) . ')(...func_get_args());';
        $f->setBody($body);
        $write('if (function_exists("\\rikmeijer\\Bootstrap\\types\\' . basename($resourcePath, '.php') . '") === false) {' . $f->__toString() . '}');
    }, $write));
    fclose($fp);
}