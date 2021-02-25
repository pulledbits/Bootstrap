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
        $bootstrap = Bootstrap::initialize($this->getConfigurationRoot());
        self::assertEquals($value, $bootstrap('resource')->option);

        $this->streams['config'] = fopen($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'config.php', 'wb');
    }

    private function createConfig(string $streamID, array $config): void
    {
        ftruncate($this->streams[$streamID], 0);
        fwrite($this->streams[$streamID], '<?php return ' . var_export($config, true) . ';');
    }

    private function createFunction(string $resourceName, string $content): void
    {
        if (str_contains($resourceName, '/')) {
            mkdir(dirname($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . $resourceName), 0777, true);
        }
        file_put_contents($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . $resourceName . '.php', $content);
    }

    public function testConfig_CustomOption(): void
    {
        $value = uniqid('', true);
        $value2 = uniqid('', true);

        $this->createConfig('config', ["resourceCustom" => ["option" => $value2]]);
        $this->createFunction('resourceCustom', '<?php ' . PHP_EOL . '$configuration = $validate(["option" => rikmeijer\\Bootstrap\\Configuration::default("' . $value . '")]); ' . PHP_EOL . 'return function() use ($configuration) {' . PHP_EOL . '   return (object)["option" => $configuration["option"]];' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getConfigurationRoot());
        self::assertEquals($value2, $bootstrap('resourceCustom')->option);
    }

    public function testConfig_CustomOption_RecursiveMerge(): void
    {
        $value = uniqid('', true);

        $this->createConfig('config', ["resource" => ["option2" => "custom"]]);
        $this->createFunction('resource', '<?php ' . PHP_EOL . '$configuration = $validate(["option1" => rikmeijer\\Bootstrap\\Configuration::default("' . $value . '")]); ' . PHP_EOL . 'return function() use ($configuration) { ' . PHP_EOL . '   return (object)["option" => $configuration["option1"]];' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getConfigurationRoot());

        self::assertEquals($value, $bootstrap('resource')->option);
    }

    public function testResource(): void
    {
        $this->createFunction('resource', '<?php ' . PHP_EOL . '$configuration = $validate([]); ' . PHP_EOL . 'return function() use ($configuration) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getConfigurationRoot());

        self::assertEquals('Yes!', $bootstrap('resource')->status);
    }

    public function testWhen_Called_Expect_FunctionAvailableAsStaticMethod(): void
    {
        $this->createConfig('config', ['BOOTSTRAP' => ['namespace' => 'rikmeijer\\Bootstrap\\f']]);
        $this->createFunction('resourceFunc', '<?php ' . PHP_EOL . '$configuration = $validate([]); ' . PHP_EOL . 'return function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) use ($configuration) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getConfigurationRoot());
        $args = ['foo', null, $this->createMock(ReflectionFunction::class), 3.14];
        $bootstrap('resourceFunc', ...$args);
        $f = '\\rikmeijer\\Bootstrap\\f\\resourceFunc';
        self::assertEquals('Yes!', $f(...$args)->status);
    }


    public function testResourceWhenExtraArgumentsArePassed_Expect_ParametersAvailable(): void
    {
        $this->createFunction('resource', '<?php ' . PHP_EOL . '$configuration = $validate([]); ' . PHP_EOL . 'return function(string $extratext) use ($configuration) { ' . PHP_EOL . '   return (object)["status" => "Yes!" . $extratext]; 
            };');

        $bootstrap = Bootstrap::initialize($this->getConfigurationRoot());

        self::assertEquals('Yes!Hello World', $bootstrap('resource', 'Hello World')->status);
    }

    public function testResourceWithoutTypehintForConfig(): void
    {
        $this->createFunction('resource', '<?php ' . PHP_EOL . '$configuration = $validate([]); ' . PHP_EOL . 'return function() use ($configuration) { ' . PHP_EOL . '    return (object)["status" => "Yes!"]; ' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getConfigurationRoot());

        self::assertEquals('Yes!', $bootstrap('resource')->status);
    }

    public function testWhenConfigurationSectionMatchesResourcesName_ExpectConfigurationToBePassedToBootstrapper(): void
    {
        $value = uniqid('', true);

        $this->createFunction('resource', '<?php ' . PHP_EOL . '$configuration = $validate(["status" => rikmeijer\\Bootstrap\\Configuration::default("' . $value . '")]); ' . PHP_EOL . 'return function() use ($configuration) { ' . PHP_EOL . '    return (object)["status" => $configuration["status"]]; ' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getConfigurationRoot());

        self::assertEquals($value, $bootstrap('resource')->status);
    }


    public function testWhenConfigurationMissingPath_ExpectConfigurationWithPathRelativeToConfigurationPath(): void
    {
        $this->createFunction('resource', '<?php ' . PHP_EOL . '$configuration = $validate(["path" => rikmeijer\\Bootstrap\\Configuration::path("somedir")]); ' . PHP_EOL . 'return function() use ($configuration) { ' . PHP_EOL . '    return (object)["status" => $configuration["path"]];' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getConfigurationRoot());

        self::assertEquals(Path::join($this->getConfigurationRoot(), 'somedir'), $bootstrap('resource')->status);
    }

    public function testWhenConfigurationMissingPatheWithSubdirs_ExpectJoinedAbsolutePath(): void
    {
        $this->createFunction('resource', '<?php ' . PHP_EOL . '$configuration = $validate(["path" => rikmeijer\\Bootstrap\\Configuration::path("somedir", "somesubdir")]); ' . PHP_EOL . 'return function() use ($configuration) { ' . PHP_EOL . '    return (object)["status" => $configuration["path"]]; ' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getConfigurationRoot());

        self::assertEquals(Path::join($this->getConfigurationRoot(), 'somedir', 'somesubdir'), $bootstrap('resource')->status);
    }

    public function testWhenConfigurationRelativePath_ExpectAbsolutePath(): void
    {
        $this->createConfig('config', ["resource" => ["path" => "somefolder"]]);
        $this->createFunction('resource', '<?php ' . PHP_EOL . '$configuration = $validate(["path" => rikmeijer\\Bootstrap\\Configuration::path("somedir")]); ' . PHP_EOL . 'return function() use ($configuration) { ' . PHP_EOL . '    return (object)["status" => $configuration["path"]]; ' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getConfigurationRoot());

        self::assertEquals(Path::join($this->getConfigurationRoot(), 'somefolder'), $bootstrap('resource')->status);
    }

    public function testWhenResourceDependentOfOtherResource_Expect_ResourcesVariableCallableAndReturningDependency(): void
    {
        $value = uniqid('', true);

        $this->createFunction('dependency', '<?php ' . PHP_EOL . '$configuration = $validate(["status" => rikmeijer\\Bootstrap\\Configuration::default("' . $value . '")]); ' . PHP_EOL . 'return function() use ($configuration) : object {' . PHP_EOL . '   return (object)["status" => $configuration["status"]]; ' . PHP_EOL . '};');

        $this->createFunction('resourceDependent', '<?php ' . PHP_EOL . 'return function() use ($bootstrap) { ' . PHP_EOL . '   return (object)["status" => $bootstrap("dependency")->status]; ' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getConfigurationRoot());

        self::assertEquals($value, $bootstrap('resourceDependent')->status);
    }

    public function testWhenResourceDependentOfOtherResourceWithExtraArguments_Expect_ExtraParametersAvailableInDependency(): void
    {
        $value = uniqid('', true);

        $this->createFunction('dependency', '<?php ' . PHP_EOL . '$configuration = $validate(["status" => rikmeijer\\Bootstrap\\Configuration::default("' . $value . '")]); ' . PHP_EOL . 'return function(string $extratext) use ($configuration) : object { ' . PHP_EOL . '   return (object)["status" => $configuration["status"] . $extratext]; ' . PHP_EOL . '};');

        $this->createFunction('resourceDependent', '<?php' . PHP_EOL . 'return function() use ($bootstrap) {' . PHP_EOL . '   return (object)["status" => $bootstrap("dependency", "Hello World!")->status]; ' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getConfigurationRoot());

        self::assertEquals($value . 'Hello World!', $bootstrap('resourceDependent')->status);
    }

    public function testWhenNoConfigurationIsRequired_ExpectOnlyDependenciesInjectedByBootstrap(): void
    {
        $value = uniqid('', true);

        $this->createFunction('dependency2', '<?php ' . PHP_EOL . '$configuration = $validate(["status" => rikmeijer\\Bootstrap\\Configuration::default("' . $value . '")]);' . PHP_EOL . 'return function() use ($configuration) : object { ' . PHP_EOL . '   return (object)["status" => $configuration["status"]]; ' . PHP_EOL . '};');

        $this->createFunction('resourceDependent2', '<?php ' . PHP_EOL . 'return function() use ($bootstrap) { ' . PHP_EOL . '   return (object)["status" => $bootstrap("dependency2")->status]; ' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getConfigurationRoot());

        self::assertEquals($value, $bootstrap('resourceDependent2')->status);
    }

    public function testResourceCache(): void
    {
        $this->createFunction('resourceCache', '<?php ' . PHP_EOL . 'return function() { ' . PHP_EOL . '   return (object)["status" => "Yes!"]; ' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getConfigurationRoot());
        self::assertEquals('Yes!', $bootstrap('resourceCache')->status);

        $this->createFunction('resourceCache', '<?php ' . PHP_EOL . 'return function() { ' . PHP_EOL . '   return (object)["status" => "No!"];' . PHP_EOL . '};');
        self::assertNotInstanceOf(Closure::class, $bootstrap('resourceCache'));
        self::assertEquals('Yes!', $bootstrap('resourceCache')->status);

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
