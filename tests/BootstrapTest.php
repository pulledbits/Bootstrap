<?php

namespace pulledbits\Bootstrap;


class BootstrapTest extends \PHPUnit\Framework\TestCase
{

    public function testConfig_DefaultOption()
    {
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php';
        file_put_contents($file, '<?php return ["SECTION" => ["option" => "value"]];');

        $object = new Bootstrap(sys_get_temp_dir());

        $this->assertEquals('value', $object->config('SECTION')['option']);
    }

    public function testResource()
    {
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'config.defaults.php';
        file_put_contents($file, '<?php return ["BOOTSTRAP" => ["path" => __DIR__]];');
        file_put_contents(dirname($file) . DIRECTORY_SEPARATOR . 'resource.php', '<?php return function(\\pulledbits\\Bootstrap\\Bootstrap $bootstrap) { return "Yes!"; };');

        $object = new Bootstrap(sys_get_temp_dir());

        $this->assertEquals('Yes!', $object->resource('resource'));

    }
}
