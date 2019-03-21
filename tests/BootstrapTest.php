<?php

namespace pulledbits\Bootstrap;


class BootstrapTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        @unlink(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php');
        @unlink(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.php');
        @unlink(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'resource.php');
    }


    public function testConfig_DefaultOption()
    {
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php', '<?php return ["SECTION" => ["option" => "value"]];');

        $object = new Bootstrap(sys_get_temp_dir());

        $this->assertEquals('value', $object->config('SECTION')['option']);
    }

    public function testConfig_PreloadAssets()
    {
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php', '<?php return ["BOOTSTRAP" => ["path" => __DIR__, "preload" => ["resource"]]];');
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'resource.php', '<?php return function(\\pulledbits\\Bootstrap\\Bootstrap $bootstrap) { return "Yes!"; };');

        $object = new Bootstrap(sys_get_temp_dir());
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'resource.php', '<?php return function(\\pulledbits\\Bootstrap\\Bootstrap $bootstrap) { return "No!"; };');
        $this->assertEquals('Yes!', $object->resource('resource'));
    }

    public function testConfig_CustomOption()
    {
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php', '<?php return ["SECTION" => ["option" => "value"]];');
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.php', '<?php return ["SECTION" => ["option" => "custom"]];');

        $object = new Bootstrap(sys_get_temp_dir());

        $this->assertEquals('custom', $object->config('SECTION')['option']);
    }

    public function testResource()
    {
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php', '<?php return ["BOOTSTRAP" => ["path" => __DIR__]];');
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'resource.php', '<?php return function(\\pulledbits\\Bootstrap\\Bootstrap $bootstrap) { return "Yes!"; };');

        $object = new Bootstrap(sys_get_temp_dir());

        $this->assertEquals('Yes!', $object->resource('resource'));
    }

    public function testResourceCache()
    {
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php', '<?php return ["BOOTSTRAP" => ["path" => __DIR__]];');
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'resource.php', '<?php return function(\\pulledbits\\Bootstrap\\Bootstrap $bootstrap) { return "Yes!"; };');

        $object = new Bootstrap(sys_get_temp_dir());
        $this->assertEquals('Yes!', $object->resource('resource'));

        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'resource.php', '<?php return function(\\pulledbits\\Bootstrap\\Bootstrap $bootstrap) { return "No!"; };');
        $this->assertEquals('Yes!', $object->resource('resource'));

    }
}
