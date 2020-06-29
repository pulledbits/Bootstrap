<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\tests;

use PHPUnit\Framework\TestCase;
use rikmeijer\Bootstrap\Bootstrap;

final class BootstrapTest extends TestCase
{
    public function testConfig_DefaultOption() : void
    {
        $value = uniqid('', true);

        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php', '<?php return ["BOOTSTRAP" => ["path" => __DIR__], "resource" => ["option" => "'.$value.'"]];');
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'resource.php', '<?php return function(\\rikmeijer\\Bootstrap\\Bootstrap $bootstrap, array $configuration) { return (object)["option" => $configuration["option"]]; };');

        $object = new Bootstrap(sys_get_temp_dir());

        $this->assertEquals($value, $object->resource('resource')->option);
    }

    public function testConfig_CustomOption() : void
    {
        $value = uniqid('', true);

        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php', '<?php return ["BOOTSTRAP" => ["path" => __DIR__], "resource" => ["option" => "value"]];');
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.php', '<?php return ["resource" => ["option" => "'.$value.'"]];');
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'resource.php', '<?php return function(\\rikmeijer\\Bootstrap\\Bootstrap $bootstrap, array $configuration) { return (object)["option" => $configuration["option"]]; };');

        $object = new Bootstrap(sys_get_temp_dir());
        $this->assertEquals($value, $object->resource('resource')->option);
    }

    public function testConfig_CustomOption_RecursiveMerge() : void
    {
        $value = uniqid('', true);

        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php', '<?php return ["BOOTSTRAP" => ["path" => __DIR__], "resource" => ["option1" => "'.$value.'"]];');
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.php', '<?php return ["resource" => ["option2" => "custom"]];');
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'resource.php', '<?php return function(\\rikmeijer\\Bootstrap\\Bootstrap $bootstrap, array $configuration) { return (object)["option" => $configuration["option1"]]; };');

        $object = new Bootstrap(sys_get_temp_dir());

        $this->assertEquals($value, $object->resource('resource')->option);
    }

    public function testResource() : void
    {
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php', '<?php return ["BOOTSTRAP" => ["path" => __DIR__]];');
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'resource.php', '<?php return function(\\rikmeijer\\Bootstrap\\Bootstrap $bootstrap) { return (object)["status" => "Yes!"]; };');

        $object = new Bootstrap(sys_get_temp_dir());

        $this->assertEquals('Yes!', $object->resource('resource')->status);
    }

    public function testWhenConfigurationSectionMatchesResourcesName_ExpectConfigurationToBePassedToBootstrapper() : void
    {
        $value = uniqid('', true);

        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php', '<?php return ["BOOTSTRAP" => ["path" => __DIR__], "resource" => ["status" => "'.$value.'"]];');
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'resource.php', '<?php return function(\\rikmeijer\\Bootstrap\\Bootstrap $bootstrap, array $configuration) { return (object)["status" => $configuration["status"]]; };');

        $object = new Bootstrap(sys_get_temp_dir());

        $this->assertEquals($value, $object->resource('resource')->status);
    }

    public function testResourceCache() : void
    {
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php', '<?php return ["BOOTSTRAP" => ["path" => __DIR__]];');
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
