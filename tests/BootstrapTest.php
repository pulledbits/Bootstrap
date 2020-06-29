<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\tests;

use PHPUnit\Framework\TestCase;
use rikmeijer\Bootstrap\Bootstrap;

final class BootstrapTest extends TestCase
{
    private array $streams;

    protected function setUp() : void
    {
        $this->streams['config.default'] = fopen(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php', 'wb');
        $this->streams['config'] = fopen(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.php', 'wb');

        if (is_dir(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bootstrap') === false) {
            mkdir(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bootstrap');
        }
    }
    protected function tearDown() : void
    {
        fclose($this->streams['config.default']);
        @unlink(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php');

        fclose($this->streams['config']);
        @unlink(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.php');

        @unlink(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'resource.php');

        @unlink(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'resource.php');
        @rmdir(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bootstrap');
    }

    private function createConfig(string $streamID, array $config) : void {
        fwrite($this->streams[$streamID], '<?php return ' . var_export($config, true) . ';');
    }

    private function createResource(string $resourceName, string $content) : void
    {
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . $resourceName . '.php', $content);
    }

    public function testConfig_DefaultOption() : void
    {
        $value = uniqid('', true);

        $this->createConfig('config.default', ["resource" => ["option" => $value]]);
        $this->createResource('resource', '<?php return function(\\rikmeijer\\Bootstrap\\Bootstrap $bootstrap, array $configuration) { return (object)["option" => $configuration["option"]]; };');

        $object = new Bootstrap(sys_get_temp_dir());

        $this->assertEquals($value, $object->resource('resource')->option);
    }

    public function testConfig_CustomOption() : void
    {
        $value = uniqid('', true);

        $this->createConfig('config.default', ["resource" => ["option" => $value]]);
        $this->createConfig('config', ["resource" => ["option" => $value]]);
        $this->createResource('resource', '<?php return function(\\rikmeijer\\Bootstrap\\Bootstrap $bootstrap, array $configuration) { return (object)["option" => $configuration["option"]]; };');

        $object = new Bootstrap(sys_get_temp_dir());
        $this->assertEquals($value, $object->resource('resource')->option);
    }

    public function testConfig_CustomOption_RecursiveMerge() : void
    {
        $value = uniqid('', true);

        $this->createConfig('config.default', ["resource" => ["option1" => $value]]);
        $this->createConfig('config', ["resource" => ["option2" => "custom"]]);
        $this->createResource('resource', '<?php return function(\\rikmeijer\\Bootstrap\\Bootstrap $bootstrap, array $configuration) { return (object)["option" => $configuration["option1"]]; };');

        $object = new Bootstrap(sys_get_temp_dir());

        $this->assertEquals($value, $object->resource('resource')->option);
    }

    public function testResource() : void
    {
        $this->createConfig('config.default', ["BOOTSTRAP" => ["path" => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bootstrap']]);
        $this->createResource('resource', '<?php return function(\\rikmeijer\\Bootstrap\\Bootstrap $bootstrap) { return (object)["status" => "Yes!"]; };');

        $object = new Bootstrap(sys_get_temp_dir());

        $this->assertEquals('Yes!', $object->resource('resource')->status);
    }


    public function testWhenNoResourcePathIsConfigured_ExpectBootstrapDirectoryUnderConfigurationPathToBeUsed() : void
    {
        $this->createConfig('config.default', []);
        $this->createResource('resource', '<?php return function(\\rikmeijer\\Bootstrap\\Bootstrap $bootstrap) { return (object)["status" => "Yes!"]; };');

        $object = new Bootstrap(sys_get_temp_dir());

        $this->assertEquals('Yes!', $object->resource('resource')->status);

    }


    public function testWhenConfigurationSectionMatchesResourcesName_ExpectConfigurationToBePassedToBootstrapper() : void
    {
        $value = uniqid('', true);

        $this->createConfig('config.default', ["resource" => ["status" => $value]]);
        $this->createResource('resource', '<?php return function(\\rikmeijer\\Bootstrap\\Bootstrap $bootstrap, array $configuration) { return (object)["status" => $configuration["status"]]; };');

        $object = new Bootstrap(sys_get_temp_dir());

        $this->assertEquals($value, $object->resource('resource')->status);
    }

    public function testResourceCache() : void
    {
        $this->createConfig('config.default', []);
        $this->createResource('resource', '<?php return function(\\rikmeijer\\Bootstrap\\Bootstrap $bootstrap) { return (object)["status" => "Yes!"]; };');

        $object = new Bootstrap(sys_get_temp_dir());
        $this->assertEquals('Yes!', $object->resource('resource')->status);

        $this->createResource('resource', '<?php return function(\\rikmeijer\\Bootstrap\\Bootstrap $bootstrap) { return (object)["status" => "No!"];};');
        $this->assertEquals('Yes!', $object->resource('resource')->status);

    }
}
