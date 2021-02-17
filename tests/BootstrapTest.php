<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\tests;

use Closure;
use PHPUnit\Framework\TestCase;
use rikmeijer\Bootstrap\Bootstrap;

final class BootstrapTest extends TestCase
{
    private array $streams;

    public function testConfig_DefaultOption(): void
    {
        $value = uniqid('', true);
        $this->createConfig('config.default', ["resource" => ["option" => $value]]);
        fclose($this->streams['config']);
        @unlink($this->getResourcesRoot() . DIRECTORY_SEPARATOR . 'config.php');
        $this->createResource('resource', '<?php $configuration = $validate([]); return function() use ($configuration) { return (object)["option" => $configuration["option"]]; };');

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

        $this->createConfig('config.default', ["resource-custom" => ["option" => $value]]);
        $this->createConfig('config', ["resource-custom" => ["option" => $value2]]);
        $this->createResource('resource-custom', '<?php $configuration = $validate([]); return function() use ($configuration) { return (object)["option" => $configuration["option"]]; };');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());
        self::assertEquals($value2, $bootstrap('resource-custom')->option);
    }

    public function testConfig_CustomOption_RecursiveMerge(): void
    {
        $value = uniqid('', true);

        $this->createConfig('config.default', ["resource" => ["option1" => $value]]);
        $this->createConfig('config', ["resource" => ["option2" => "custom"]]);
        $this->createResource('resource', '<?php $configuration = $validate([]); return function() use ($configuration) { return (object)["option" => $configuration["option1"]]; };');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());

        self::assertEquals($value, $bootstrap('resource')->option);
    }

    public function testResource(): void
    {
        $this->createConfig('config.default', ["BOOTSTRAP" => ["path" => $this->getResourcesRoot() . DIRECTORY_SEPARATOR . 'bootstrap']]);
        $this->createResource('resource', '<?php $configuration = $validate([]); return function() use ($configuration) { return (object)["status" => "Yes!"]; };');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());

        self::assertEquals('Yes!', $bootstrap('resource')->status);
    }

    public function testResourceWhenExtraArgumentsArePassed_Expect_ParametersAvailable(): void
    {
        $this->createConfig('config.default', ["BOOTSTRAP" => ["path" => $this->getResourcesRoot() . DIRECTORY_SEPARATOR . 'bootstrap']]);
        $this->createResource('resource', '<?php $configuration = $validate([]); return function(string $extratext) use ($configuration) { return (object)["status" => "Yes!" . $extratext]; };');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());

        self::assertEquals('Yes!Hello World', $bootstrap('resource', 'Hello World')->status);
    }

    public function testResourceWithoutTypehintForConfig(): void
    {
        $this->createConfig('config.default', ["BOOTSTRAP" => ["path" => $this->getResourcesRoot() . DIRECTORY_SEPARATOR . 'bootstrap']]);
        $this->createResource('resource', '<?php $configuration = $validate([]); return function() use ($configuration) { return (object)["status" => "Yes!"]; };');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());

        self::assertEquals('Yes!', $bootstrap('resource')->status);
    }

    public function testWhenNoResourcePathIsConfigured_ExpectBootstrapDirectoryUnderConfigurationPathToBeUsed(): void
    {
        $this->createConfig('config.default', []);
        $this->createResource('resource', '<?php $configuration = $validate([]); return function() use ($configuration) { return (object)["status" => "Yes!"]; };');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());

        self::assertEquals('Yes!', $bootstrap('resource')->status);

    }

    public function testWhenConfigurationSectionMatchesResourcesName_ExpectConfigurationToBePassedToBootstrapper(): void
    {
        $value = uniqid('', true);

        $this->createResource('resource', '<?php $configuration = $validate(["status" => rikmeijer\\Bootstrap\\Configuration::default("' . $value . '")]); return function() use ($configuration) { return (object)["status" => $configuration["status"]]; };');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());

        self::assertEquals($value, $bootstrap('resource')->status);
    }

    public function testWhenResourceDependentOfOtherResource_Expect_ResourcesVariableCallableAndReturningDependency(): void
    {
        $value = uniqid('', true);

        $this->createConfig('config.default', ["BOOTSTRAP" => [], "dependency" => ["status" => $value]]);
        $this->createResource('dependency', '<?php $configuration = $validate([]); return function() use ($configuration) : object { return (object)["status" => $configuration["status"]]; };');

        $this->createResource('resource-dependent', '<?php
        return function() use ($bootstrap) { return (object)["status" => $bootstrap("dependency")->status]; 
        };');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());

        self::assertEquals($value, $bootstrap('resource-dependent')->status);
    }

    public function testWhenResourceDependentOfOtherResourceWithExtraArguments_Expect_ExtraParametersAvailableInDependency(): void
    {
        $value = uniqid('', true);

        $this->createConfig('config.default', ["BOOTSTRAP" => [], "dependency" => ["status" => $value]]);
        $this->createResource('dependency', '<?php $configuration = $validate([]); return function(string $extratext) use ($configuration) : object { return (object)["status" => $configuration["status"] . $extratext]; };');

        $this->createResource('resource-dependent', '<?php
        return function() use ($bootstrap) { return (object)["status" => $bootstrap("dependency", "Hello World!")->status]; 
        };');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());

        self::assertEquals($value . 'Hello World!', $bootstrap('resource-dependent')->status);
    }

    public function testWhenNoConfigurationIsRequired_ExpectOnlyDependenciesInjectedByBootstrap(): void
    {
        $value = uniqid('', true);

        $this->createConfig('config.default', ["BOOTSTRAP" => [], "dependency2" => ["status" => $value]]);
        $this->createResource('dependency2', '<?php $configuration = $validate([]); return function() use ($configuration) : object { return (object)["status" => $configuration["status"]]; };');

        $this->createResource('resource-dependent2', '<?php
        return function() use ($bootstrap) { return (object)["status" => $bootstrap("dependency2")->status]; 
        };');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());

        self::assertEquals($value, $bootstrap('resource-dependent2')->status);
    }

    public function testResourceCache(): void
    {
        $this->createConfig('config.default', []);
        $this->createResource('resource-cache', '<?php return function() { return (object)["status" => "Yes!"]; };');

        $bootstrap = Bootstrap::initialize($this->getResourcesRoot());
        self::assertEquals('Yes!', $bootstrap('resource-cache')->status);

        $this->createResource('resource-cache', '<?php return function() { return (object)["status" => "No!"];};');
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
        $this->streams['config.default'] = fopen($this->getResourcesRoot() . DIRECTORY_SEPARATOR . 'config.defaults.php', 'wb');
        $this->streams['config'] = fopen($this->getResourcesRoot() . DIRECTORY_SEPARATOR . 'config.php', 'wb');

        if (is_dir($this->getResourcesRoot() . DIRECTORY_SEPARATOR . 'bootstrap') === false) {
            mkdir($this->getResourcesRoot() . DIRECTORY_SEPARATOR . 'bootstrap');
        }
    }

    protected function tearDown(): void
    {
        fclose($this->streams['config.default']);
        @unlink($this->getResourcesRoot() . DIRECTORY_SEPARATOR . 'config.defaults.php');

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
