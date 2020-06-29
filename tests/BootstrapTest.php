<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\tests;

use PHPUnit\Framework\TestCase;
use rikmeijer\Bootstrap\Bootstrap;

final class BootstrapTest extends TestCase
{
    private function createConfig(string $path, array $config) {
        file_put_contents($path, '<?php return ' . var_export($config, true) . ';');
    }

    public function testConfig_DefaultOption() : void
    {
        $value = uniqid('', true);

        $this->createConfig(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php', ["BOOTSTRAP" => ["path" => sys_get_temp_dir()], "resource" => ["option" => $value]]);
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'resource.php', '<?php return function(\\rikmeijer\\Bootstrap\\Bootstrap $bootstrap, array $configuration) { return (object)["option" => $configuration["option"]]; };');

        $object = new Bootstrap(sys_get_temp_dir());

        $this->assertEquals($value, $object->resource('resource')->option);
    }

    public function testConfig_CustomOption() : void
    {
        $value = uniqid('', true);

        $this->createConfig(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php', ["BOOTSTRAP" => ["path" => sys_get_temp_dir()], "resource" => ["option" => $value]]);
        $this->createConfig(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.php', ["BOOTSTRAP" => ["path" => sys_get_temp_dir()], "resource" => ["option" => $value]]);
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'resource.php', '<?php return function(\\rikmeijer\\Bootstrap\\Bootstrap $bootstrap, array $configuration) { return (object)["option" => $configuration["option"]]; };');

        $object = new Bootstrap(sys_get_temp_dir());
        $this->assertEquals($value, $object->resource('resource')->option);
    }

    public function testConfig_CustomOption_RecursiveMerge() : void
    {
        $value = uniqid('', true);

        $this->createConfig(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php', ["BOOTSTRAP" => ["path" => sys_get_temp_dir()], "resource" => ["option1" => $value]]);
        $this->createConfig(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.php', ["BOOTSTRAP" => ["path" => sys_get_temp_dir()], "resource" => ["option2" => "custom"]]);
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'resource.php', '<?php return function(\\rikmeijer\\Bootstrap\\Bootstrap $bootstrap, array $configuration) { return (object)["option" => $configuration["option1"]]; };');

        $object = new Bootstrap(sys_get_temp_dir());

        $this->assertEquals($value, $object->resource('resource')->option);
    }

    public function testResource() : void
    {
        $this->createConfig(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php', ["BOOTSTRAP" => ["path" => sys_get_temp_dir()]]);
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'resource.php', '<?php return function(\\rikmeijer\\Bootstrap\\Bootstrap $bootstrap) { return (object)["status" => "Yes!"]; };');

        $object = new Bootstrap(sys_get_temp_dir());

        $this->assertEquals('Yes!', $object->resource('resource')->status);
    }


    public function testWhenNoResourcePathIsConfigured_ExpectBootstrapDirectoryUnderConfigurationPathToBeUsed() : void
    {
        $this->createConfig(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php', []);

        if (is_dir(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bootstrap') === false) {
            mkdir(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bootstrap');
        }

        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'resource.php', '<?php return function(\\rikmeijer\\Bootstrap\\Bootstrap $bootstrap) { return (object)["status" => "Yes!"]; };');

        $object = new Bootstrap(sys_get_temp_dir());

        $this->assertEquals('Yes!', $object->resource('resource')->status);

        unlink(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'resource.php');
        rmdir(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bootstrap');
    }


    public function testWhenConfigurationSectionMatchesResourcesName_ExpectConfigurationToBePassedToBootstrapper() : void
    {
        $value = uniqid('', true);

        $this->createConfig(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php', ["BOOTSTRAP" => ["path" => sys_get_temp_dir()], "resource" => ["status" => $value]]);
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'resource.php', '<?php return function(\\rikmeijer\\Bootstrap\\Bootstrap $bootstrap, array $configuration) { return (object)["status" => $configuration["status"]]; };');

        $object = new Bootstrap(sys_get_temp_dir());

        $this->assertEquals($value, $object->resource('resource')->status);
    }

    public function testResourceCache() : void
    {
        $this->createConfig(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php', ["BOOTSTRAP" => ["path" => sys_get_temp_dir()]]);
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'resource.php', '<?php return function(\\rikmeijer\\Bootstrap\\Bootstrap $bootstrap) { return (object)["status" => "Yes!"]; };');

        $object = new Bootstrap(sys_get_temp_dir());
        $this->assertEquals('Yes!', $object->resource('resource')->status);

        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'resource.php', '<?php return function(\\rikmeijer\\Bootstrap\\Bootstrap $bootstrap) { return (object)["status" => "No!"];};');
        $this->assertEquals('Yes!', $object->resource('resource')->status);

    }

    protected function setUp() : void
    {
        @unlink(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php');
        @unlink(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.php');
        @unlink(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'resource.php');
    }
}
