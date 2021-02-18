<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\tests;

use Closure;
use PHPUnit\Framework\TestCase;
use rikmeijer\Bootstrap\Bootstrap;
use Webmozart\PathUtil\Path;

final class BootstrapTest extends TestCase
{
    private array $streams;

    public function testConfig_DefaultOption(): void
    {
        $value = uniqid('', true);
        $this->createResource('resource', '<?php ' . PHP_EOL . '$configuration = $validate(["option" => rikmeijer\\Bootstrap\\Configuration::default("' . $value . '")]); ' . PHP_EOL . 'return function() use ($configuration) { ' . PHP_EOL . '  return (object)["option" => $configuration["option"]]; ' . PHP_EOL . '};');

        // Act
        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());
        self::assertEquals($value, $bootstrap('resource')->option);

        $this->streams['config'] = fopen($this->getResourcesRoot() . DIRECTORY_SEPARATOR . 'config.php', 'wb');
    }

    private function createConfig(string $streamID, array $config): void
    {
        ftruncate($this->streams[$streamID], 0);
        fwrite($this->streams[$streamID], '<?php return ' . var_export($config, true) . ';');
    }

    private function createResource(string $resourceName, string $content): void
    {
        file_put_contents($this->getResourcesRoot() . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . $resourceName . '.php', $content);
    }

    public function testConfig_CustomOption(): void
    {
        $value = uniqid('', true);
        $value2 = uniqid('', true);

        $this->createConfig('config', ["resource-custom" => ["option" => $value2]]);
        $this->createResource('resource-custom', '<?php ' . PHP_EOL . '$configuration = $validate(["option" => rikmeijer\\Bootstrap\\Configuration::default("' . $value . '")]); ' . PHP_EOL . 'return function() use ($configuration) {' . PHP_EOL . '   return (object)["option" => $configuration["option"]];' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());
        self::assertEquals($value2, $bootstrap('resource-custom')->option);
    }

    public function testConfig_CustomOption_RecursiveMerge(): void
    {
        $value = uniqid('', true);

        $this->createConfig('config', ["resource" => ["option2" => "custom"]]);
        $this->createResource('resource', '<?php ' . PHP_EOL . '$configuration = $validate(["option1" => rikmeijer\\Bootstrap\\Configuration::default("' . $value . '")]); ' . PHP_EOL . 'return function() use ($configuration) { ' . PHP_EOL . '   return (object)["option" => $configuration["option1"]];' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());

        self::assertEquals($value, $bootstrap('resource')->option);
    }

    public function testResource(): void
    {
        $this->createResource('resource', '<?php ' . PHP_EOL . '$configuration = $validate([]); ' . PHP_EOL . 'return function() use ($configuration) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());

        self::assertEquals('Yes!', $bootstrap('resource')->status);
    }

    public function testResourceWhenExtraArgumentsArePassed_Expect_ParametersAvailable(): void
    {
        $this->createResource('resource', '<?php ' . PHP_EOL . '$configuration = $validate([]); ' . PHP_EOL . 'return function(string $extratext) use ($configuration) { ' . PHP_EOL . '   return (object)["status" => "Yes!" . $extratext]; 
            };');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());

        self::assertEquals('Yes!Hello World', $bootstrap('resource', 'Hello World')->status);
    }

    public function testResourceWithoutTypehintForConfig(): void
    {
        $this->createResource('resource', '<?php ' . PHP_EOL . '$configuration = $validate([]); ' . PHP_EOL . 'return function() use ($configuration) { ' . PHP_EOL . '    return (object)["status" => "Yes!"]; ' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());

        self::assertEquals('Yes!', $bootstrap('resource')->status);
    }

    public function testWhenConfigurationSectionMatchesResourcesName_ExpectConfigurationToBePassedToBootstrapper(): void
    {
        $value = uniqid('', true);

        $this->createResource('resource', '<?php ' . PHP_EOL . '$configuration = $validate(["status" => rikmeijer\\Bootstrap\\Configuration::default("' . $value . '")]); ' . PHP_EOL . 'return function() use ($configuration) { ' . PHP_EOL . '    return (object)["status" => $configuration["status"]]; ' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());

        self::assertEquals($value, $bootstrap('resource')->status);
    }


    public function testWhenConfigurationMissingPath_ExpectConfigurationWithPathRelativeToConfigurationPath(): void
    {
        $this->createResource('resource', '<?php ' . PHP_EOL . '$configuration = $validate(["path" => rikmeijer\\Bootstrap\\Configuration::path("somedir")]); ' . PHP_EOL . 'return function() use ($configuration) { ' . PHP_EOL . '    return (object)["status" => $configuration["path"]];' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());

        self::assertEquals(Path::join($this->getResourcesRoot(), 'somedir'), $bootstrap('resource')->status);
    }

    public function testWhenConfigurationMissingPatheWihtSubdirs_ExpectJoinedAbsolutePath(): void
    {
        $this->createResource('resource', '<?php ' . PHP_EOL . '$configuration = $validate(["path" => rikmeijer\\Bootstrap\\Configuration::path("somedir", "somesubdir")]); ' . PHP_EOL . 'return function() use ($configuration) { ' . PHP_EOL . '    return (object)["status" => $configuration["path"]]; ' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());

        self::assertEquals(Path::join($this->getResourcesRoot(), 'somedir', 'somesubdir'), $bootstrap('resource')->status);
    }

    public function testWhenConfigurationRelativePath_ExpectAbsolutePath(): void
    {
        $this->createConfig('config', ["resource" => ["path" => "somefolder"]]);
        $this->createResource('resource', '<?php ' . PHP_EOL . '$configuration = $validate(["path" => rikmeijer\\Bootstrap\\Configuration::path("somedir")]); ' . PHP_EOL . 'return function() use ($configuration) { ' . PHP_EOL . '    return (object)["status" => $configuration["path"]]; ' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());

        self::assertEquals(Path::join($this->getResourcesRoot(), 'somefolder'), $bootstrap('resource')->status);
    }

    public function testWhenResourceDependentOfOtherResource_Expect_ResourcesVariableCallableAndReturningDependency(): void
    {
        $value = uniqid('', true);

        $this->createResource('dependency', '<?php ' . PHP_EOL . '$configuration = $validate(["status" => rikmeijer\\Bootstrap\\Configuration::default("' . $value . '")]); ' . PHP_EOL . 'return function() use ($configuration) : object {' . PHP_EOL . '   return (object)["status" => $configuration["status"]]; ' . PHP_EOL . '};');

        $this->createResource('resource-dependent', '<?php ' . PHP_EOL . 'return function() use ($bootstrap) { ' . PHP_EOL . '   return (object)["status" => $bootstrap("dependency")->status]; ' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());

        self::assertEquals($value, $bootstrap('resource-dependent')->status);
    }

    public function testWhenResourceDependentOfOtherResourceWithExtraArguments_Expect_ExtraParametersAvailableInDependency(): void
    {
        $value = uniqid('', true);

        $this->createResource('dependency', '<?php ' . PHP_EOL . '$configuration = $validate(["status" => rikmeijer\\Bootstrap\\Configuration::default("' . $value . '")]); ' . PHP_EOL . 'return function(string $extratext) use ($configuration) : object { ' . PHP_EOL . '   return (object)["status" => $configuration["status"] . $extratext]; ' . PHP_EOL . '};');

        $this->createResource('resource-dependent', '<?php' . PHP_EOL . 'return function() use ($bootstrap) {' . PHP_EOL . '   return (object)["status" => $bootstrap("dependency", "Hello World!")->status]; ' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());

        self::assertEquals($value . 'Hello World!', $bootstrap('resource-dependent')->status);
    }

    public function testWhenNoConfigurationIsRequired_ExpectOnlyDependenciesInjectedByBootstrap(): void
    {
        $value = uniqid('', true);

        $this->createResource('dependency2', '<?php ' . PHP_EOL . '$configuration = $validate(["status" => rikmeijer\\Bootstrap\\Configuration::default("' . $value . '")]);' . PHP_EOL . 'return function() use ($configuration) : object { ' . PHP_EOL . '   return (object)["status" => $configuration["status"]]; ' . PHP_EOL . '};');

        $this->createResource('resource-dependent2', '<?php ' . PHP_EOL . 'return function() use ($bootstrap) { ' . PHP_EOL . '   return (object)["status" => $bootstrap("dependency2")->status]; ' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());

        self::assertEquals($value, $bootstrap('resource-dependent2')->status);
    }

    public function testResourceCache(): void
    {
        $this->createResource('resource-cache', '<?php ' . PHP_EOL . 'return function() { ' . PHP_EOL . '   return (object)["status" => "Yes!"]; ' . PHP_EOL . '};');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());
        self::assertEquals('Yes!', $bootstrap('resource-cache')->status);

        $this->createResource('resource-cache', '<?php ' . PHP_EOL . 'return function() { ' . PHP_EOL . '   return (object)["status" => "No!"];' . PHP_EOL . '};');
        self::assertNotInstanceOf(Closure::class, $bootstrap('resource-cache'));
        self::assertEquals('Yes!', $bootstrap('resource-cache')->status);

    }

    private function getResourcesRoot(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->getName();
    }

    protected function setUp(): void
    {
        if (!@mkdir($this->getResourcesRoot()) && !is_dir($this->getResourcesRoot())) {
            trigger_error("Unable to create " . $this->getResourcesRoot());
        }
        $this->streams['config'] = fopen($this->getResourcesRoot() . DIRECTORY_SEPARATOR . 'config.php', 'wb');

        if (is_dir($this->getResourcesRoot() . DIRECTORY_SEPARATOR . 'bootstrap') === false) {
            mkdir($this->getResourcesRoot() . DIRECTORY_SEPARATOR . 'bootstrap');
        }
    }

    protected function tearDown(): void
    {
        fclose($this->streams['config']);
        @unlink($this->getResourcesRoot() . DIRECTORY_SEPARATOR . 'config.php');

        @unlink($this->getResourcesRoot() . DIRECTORY_SEPARATOR . 'resource.php');

        foreach (glob($this->getResourcesRoot() . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . '*.php') as $tmpFile) {
            @unlink($tmpFile);
        }
        @rmdir($this->getResourcesRoot() . DIRECTORY_SEPARATOR . 'bootstrap');

        @rmdir($this->getResourcesRoot());
    }
}
