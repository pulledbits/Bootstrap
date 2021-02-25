<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\tests;

use Closure;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use rikmeijer\Bootstrap\Bootstrap;
use Webmozart\PathUtil\Path;

final class BootstrapTest extends TestCase
{
    private array $streams;

    public function testConfig_DefaultOption(): void
    {
        $value = uniqid('', true);
        $this->createFunction('resource', '<?php ' . PHP_EOL . '$configuration = $validate(["option" => rikmeijer\\Bootstrap\\Configuration::default("' . $value . '")]); ' . PHP_EOL . 'return function() use ($configuration) { ' . PHP_EOL . '  return (object)["option" => $configuration["option"]]; ' . PHP_EOL . '};');

        // Act
        Bootstrap::generate($this->getConfigurationRoot());
        Bootstrap::initialize($this->getConfigurationRoot());

        $f = $this->getFQFN('resource');
        self::assertEquals($value, $f()->option);

        $this->streams['config'] = fopen($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'config.php', 'wb');
    }

    private function createConfig(string $streamID, array $config): void
    {
        ftruncate($this->streams[$streamID], 0);
        fwrite($this->streams[$streamID], '<?php return ' . var_export($config, true) . ';');
    }

    private function createFunction(string $resourceName, string $content): void
    {
        $directory = dirname($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . $resourceName);
        if (str_contains($resourceName, '/')) {
            is_dir($directory) || mkdir($directory, 0777, true);
        }
        file_put_contents($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . $resourceName . '.php', $content);
    }

    public function testConfig_CustomOption(): void
    {
        $value = uniqid('', true);
        $value2 = uniqid('', true);

        $this->createConfig('config', ["resourceCustom" => ["option" => $value2]]);
        $this->createFunction('resourceCustom', '<?php ' . PHP_EOL . '$configuration = $validate(["option" => rikmeijer\\Bootstrap\\Configuration::default("' . $value . '")]); ' . PHP_EOL . 'return function() use ($configuration) {' . PHP_EOL . '   return (object)["option" => $configuration["option"]];' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        Bootstrap::initialize($this->getConfigurationRoot());

        $f = $this->getFQFN('resourceCustom');
        self::assertEquals($value2, $f()->option);
    }

    public function testConfig_CustomOption_RecursiveMerge(): void
    {
        $value = uniqid('', true);

        $this->createConfig('config', ["resource" => ["option2" => "custom"]]);
        $this->createFunction('resource', '<?php ' . PHP_EOL . '$configuration = $validate(["option1" => rikmeijer\\Bootstrap\\Configuration::default("' . $value . '")]); ' . PHP_EOL . 'return function() use ($configuration) { ' . PHP_EOL . '   return (object)["option" => $configuration["option1"]];' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        Bootstrap::initialize($this->getConfigurationRoot());

        $f = $this->getFQFN('resource');
        self::assertEquals($value, $f()->option);
    }

    public function testResource(): void
    {
        $this->createFunction('resource', '<?php ' . PHP_EOL . '$configuration = $validate([]); ' . PHP_EOL . 'return function() use ($configuration) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        Bootstrap::initialize($this->getConfigurationRoot());

        $f = $this->getFQFN('resource');
        self::assertEquals('Yes!', $f()->status);
    }

    public function testWhen_FunctionsFileMissing_Expect_FunctionsNotExistingButNoError(): void
    {
        $this->createFunction('resourceFunc', '<?php ' . PHP_EOL . '$configuration = $validate([]); ' . PHP_EOL . 'return function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) use ($configuration) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');

        Bootstrap::initialize($this->getConfigurationRoot());

        self::assertFalse(function_exists($this->getFQFN('resourceFunc')));
    }

    public function testWhen_VoidCalled_Expect_FunctionNotReturning(): void
    {
        $this->createConfig('config', ['BOOTSTRAP' => ['namespace' => 'rikmeijer\\Bootstrap\\f']]);
        $this->createFunction('resourceFuncVoid', '<?php ' . PHP_EOL . '$configuration = $validate([]); ' . PHP_EOL . 'return function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) use ($configuration) : void {' . PHP_EOL . ' ' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        Bootstrap::initialize($this->getConfigurationRoot());
        $args = ['foo', null, $this->createMock(ReflectionFunction::class), 3.14];
        $f = '\\rikmeijer\\Bootstrap\\f\\resourceFuncVoid';
        self::assertNull($f(...$args));
    }

    public function testWhen_Called_Expect_FunctionAvailableAsFunction(): void
    {
        $this->createConfig('config', ['BOOTSTRAP' => ['namespace' => 'rikmeijer\\Bootstrap\\f']]);
        $this->createFunction('resourceFunc', '<?php ' . PHP_EOL . '$configuration = $validate([]); ' . PHP_EOL . 'return function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) use ($configuration) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        Bootstrap::initialize($this->getConfigurationRoot());
        $args = ['foo', null, $this->createMock(ReflectionFunction::class), 3.14];
        $f = '\\rikmeijer\\Bootstrap\\f\\resourceFunc';
        self::assertEquals('Yes!', $f(...$args)->status);
    }

    public function testWhen_Called_Expect_FunctionAvailableAsFunctionUnderNS(): void
    {
        $this->createConfig('config', ['BOOTSTRAP' => ['namespace' => 'rikmeijer\\Bootstrap\\f']]);
        $this->createFunction('test/resourceFunc', '<?php ' . PHP_EOL . '$configuration = $validate([]); ' . PHP_EOL . 'return function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) use ($configuration) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        Bootstrap::initialize($this->getConfigurationRoot());
        $args = ['foo', null, $this->createMock(ReflectionFunction::class), 3.14];
        $f = '\\rikmeijer\\Bootstrap\\f\\test\\resourceFunc';
        self::assertEquals('Yes!', $f(...$args)->status);
    }

    public function testWhen_CalledDeeper_Expect_FunctionAvailableAsFunctionUnderNS(): void
    {
        $this->createFunction('test/test/resourceFunc', '<?php ' . PHP_EOL . '$configuration = $validate([]); ' . PHP_EOL . 'return function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) use ($configuration) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        Bootstrap::initialize($this->getConfigurationRoot());
        $args = ['foo', null, $this->createMock(ReflectionFunction::class), 3.14];
        $f = '\\rikmeijer\\Bootstrap\\testWhen_CalledDeeper_Expect_FunctionAvailableAsFunctionUnderNS\\test\\test\\resourceFunc';
        self::assertEquals('Yes!', $f(...$args)->status);
    }


    public function testWhen_ResourcesAreGenerated_Expect_ResourcesAvailableAsFunctions(): void
    {
        $this->createConfig('config', ['BOOTSTRAP' => ['namespace' => 'rikmeijer\\Bootstrap\\f']]);
        $this->createFunction('resourceFunc', '<?php ' . PHP_EOL . '$configuration = $validate([]); ' . PHP_EOL . 'return function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) use ($configuration) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');
        $this->createFunction('test/resourceFunc', '<?php ' . PHP_EOL . '$configuration = $validate([]); ' . PHP_EOL . 'return function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) use ($configuration) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');
        $this->createFunction('test/test/resourceFunc', '<?php ' . PHP_EOL . '$configuration = $validate([]); ' . PHP_EOL . 'return function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) use ($configuration) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());

        self::assertFileExists($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . '_f.php');

        Bootstrap::initialize($this->getConfigurationRoot());
        $args = ['foo', null, $this->createMock(ReflectionFunction::class), 3.14];

        $f = '\\rikmeijer\\Bootstrap\\f\\resourceFunc';
        self::assertEquals('Yes!', $f(...$args)->status);

        $f = '\\rikmeijer\\Bootstrap\\f\\test\\resourceFunc';
        self::assertEquals('Yes!', $f(...$args)->status);

        $f = '\\rikmeijer\\Bootstrap\\f\\test\\test\\resourceFunc';
        self::assertEquals('Yes!', $f(...$args)->status);
    }


    public function testResourceWhenExtraArgumentsArePassed_Expect_ParametersAvailable(): void
    {
        $this->createFunction('resource', '<?php ' . PHP_EOL . '$configuration = $validate([]); ' . PHP_EOL . 'return function(string $extratext) use ($configuration) { ' . PHP_EOL . '   return (object)["status" => "Yes!" . $extratext]; 
            };');

        Bootstrap::generate($this->getConfigurationRoot());
        Bootstrap::initialize($this->getConfigurationRoot());

        $f = $this->getFQFN('resource');
        self::assertEquals('Yes!Hello World', $f('Hello World')->status);
    }

    public function testResourceWithoutTypehintForConfig(): void
    {
        $this->createFunction('resource', '<?php ' . PHP_EOL . '$configuration = $validate([]); ' . PHP_EOL . 'return function() use ($configuration) { ' . PHP_EOL . '    return (object)["status" => "Yes!"]; ' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        Bootstrap::initialize($this->getConfigurationRoot());

        $f = $this->getFQFN('resource');
        self::assertEquals('Yes!', $f()->status);
    }

    public function testWhenConfigurationSectionMatchesResourcesName_ExpectConfigurationToBePassedToBootstrapper(): void
    {
        $value = uniqid('', true);

        $this->createFunction('resource', '<?php ' . PHP_EOL . '$configuration = $validate(["status" => rikmeijer\\Bootstrap\\Configuration::default("' . $value . '")]); ' . PHP_EOL . 'return function() use ($configuration) { ' . PHP_EOL . '    return (object)["status" => $configuration["status"]]; ' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        Bootstrap::initialize($this->getConfigurationRoot());

        $f = $this->getFQFN('resource');
        self::assertEquals($value, $f()->status);
    }


    public function testWhenConfigurationMissingPath_ExpectConfigurationWithPathRelativeToConfigurationPath(): void
    {
        $this->createFunction('resource', '<?php ' . PHP_EOL . '$configuration = $validate(["path" => rikmeijer\\Bootstrap\\Configuration::path("somedir")]); ' . PHP_EOL . 'return function() use ($configuration) { ' . PHP_EOL . '    return (object)["status" => $configuration["path"]];' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        Bootstrap::initialize($this->getConfigurationRoot());

        $f = $this->getFQFN('resource');
        self::assertEquals(Path::join($this->getConfigurationRoot(), 'somedir'), $f()->status);
    }

    public function testWhenConfigurationMissingPatheWithSubdirs_ExpectJoinedAbsolutePath(): void
    {
        $this->createFunction('resource', '<?php ' . PHP_EOL . '$configuration = $validate(["path" => rikmeijer\\Bootstrap\\Configuration::path("somedir", "somesubdir")]); ' . PHP_EOL . 'return function() use ($configuration) { ' . PHP_EOL . '    return (object)["status" => $configuration["path"]]; ' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        Bootstrap::initialize($this->getConfigurationRoot());

        $f = $this->getFQFN('resource');
        self::assertEquals(Path::join($this->getConfigurationRoot(), 'somedir', 'somesubdir'), $f()->status);
    }

    public function testWhenConfigurationRelativePath_ExpectAbsolutePath(): void
    {
        $this->createConfig('config', ["resource" => ["path" => "somefolder"]]);
        $this->createFunction('resource', '<?php ' . PHP_EOL . '$configuration = $validate(["path" => rikmeijer\\Bootstrap\\Configuration::path("somedir")]); ' . PHP_EOL . 'return function() use ($configuration) { ' . PHP_EOL . '    return (object)["status" => $configuration["path"]]; ' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        Bootstrap::initialize($this->getConfigurationRoot());

        $f = $this->getFQFN('resource');
        self::assertEquals(Path::join($this->getConfigurationRoot(), 'somefolder'), $f()->status);
    }

    public function testWhenResourceDependentOfOtherResource_Expect_ResourcesVariableCallableAndReturningDependency(): void
    {
        $value = uniqid('', true);

        $this->createFunction('dependency', '<?php ' . PHP_EOL . '$configuration = $validate(["status" => rikmeijer\\Bootstrap\\Configuration::default("' . $value . '")]); ' . PHP_EOL . 'return function() use ($configuration) : object {' . PHP_EOL . '   return (object)["status" => $configuration["status"]]; ' . PHP_EOL . '};');

        $this->createFunction('resourceDependent', '<?php ' . PHP_EOL . 'return function() { ' . PHP_EOL . '   return (object)["status" => ' . $this->getFQFN('dependency') . '()->status]; ' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        Bootstrap::initialize($this->getConfigurationRoot());

        $f = $this->getFQFN('resourceDependent');
        self::assertEquals($value, $f()->status);
    }

    public function testWhenResourceDependentOfOtherResourceWithExtraArguments_Expect_ExtraParametersAvailableInDependency(): void
    {
        $value = uniqid('', true);

        $this->createFunction('dependency', '<?php ' . PHP_EOL . '$configuration = $validate(["status" => rikmeijer\\Bootstrap\\Configuration::default("' . $value . '")]); ' . PHP_EOL . 'return function(string $extratext) use ($configuration) : object { ' . PHP_EOL . '   return (object)["status" => $configuration["status"] . $extratext]; ' . PHP_EOL . '};');
        $this->createFunction('resourceDependent', '<?php' . PHP_EOL . 'return function() {' . PHP_EOL . '   return (object)["status" => ' . $this->getFQFN('dependency') . '("Hello World!")->status]; ' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        Bootstrap::initialize($this->getConfigurationRoot());

        $f = $this->getFQFN('resourceDependent');
        self::assertEquals($value . 'Hello World!', $f()->status);
    }

    public function testWhenNoConfigurationIsRequired_ExpectOnlyDependenciesInjectedByBootstrap(): void
    {
        $value = uniqid('', true);

        $this->createFunction('dependency2', '<?php ' . PHP_EOL . '$configuration = $validate(["status" => rikmeijer\\Bootstrap\\Configuration::default("' . $value . '")]);' . PHP_EOL . 'return function() use ($configuration) : object { ' . PHP_EOL . '   return (object)["status" => $configuration["status"]]; ' . PHP_EOL . '};');

        $this->createFunction('resourceDependent2', '<?php ' . PHP_EOL . 'return function() { ' . PHP_EOL . '   return (object)["status" => ' . $this->getFQFN('dependency2') . '()->status]; ' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        Bootstrap::initialize($this->getConfigurationRoot());

        $f = $this->getFQFN('resourceDependent2');
        self::assertEquals($value, $f()->status);
    }

    public function testResourceCache(): void
    {
        $this->createFunction('resourceCache', '<?php ' . PHP_EOL . 'return function() { ' . PHP_EOL . '   return (object)["status" => "Yes!"]; ' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        Bootstrap::initialize($this->getConfigurationRoot());
        $f = $this->getFQFN('resourceCache');
        self::assertEquals('Yes!', $f()->status);

        $this->createFunction('resourceCache', '<?php ' . PHP_EOL . 'return function() { ' . PHP_EOL . '   return (object)["status" => "No!"];' . PHP_EOL . '};');
        self::assertNotInstanceOf(Closure::class, $f());
        self::assertEquals('Yes!', $f()->status);

    }

    private function getFQFN(string $function): string
    {
        return '\\rikmeijer\\Bootstrap\\' . $this->getName() . '\\' . $function;
    }

    private function getConfigurationRoot(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->getName();
    }

    protected function setUp(): void
    {
        if (!@mkdir($this->getConfigurationRoot()) && !is_dir($this->getConfigurationRoot())) {
            trigger_error("Unable to create " . $this->getConfigurationRoot());
        }
        $this->streams['config'] = fopen($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'config.php', 'wb');

        if (is_dir($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap') === false) {
            mkdir($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap');
        }
    }

    protected function tearDown(): void
    {
        fclose($this->streams['config']);
        @unlink($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'config.php');

        @unlink($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'resource.php');

        $this->deleteDirRecursively($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap');

        @rmdir($this->getConfigurationRoot());
    }

    private function deleteDirRecursively(string $dir): void
    {
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') as $tmpFile) {
            if (is_file($tmpFile)) {
                @unlink($tmpFile);
            } elseif (is_dir($tmpFile)) {
                $this->deleteDirRecursively($tmpFile);
            }
        }
        @rmdir($dir);
    }
}
