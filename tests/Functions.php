<?php declare(strict_types=1);


namespace rikmeijer\Bootstrap\tests;


final class Functions
{
    private array $streams;

    public function __construct(private string $root)
    {
        putenv('BOOTSTRAP_CONFIGURATION_PATH=' . $this->root);
        $this->streams['config'] = fopen($this->root . DIRECTORY_SEPARATOR . 'config.php', 'wb');
    }

    public function __destruct()
    {
        putenv('BOOTSTRAP_CONFIGURATION_PATH');
    }

    public function createConfig(string $streamID, array $config): void
    {
        ftruncate($this->streams[$streamID], 0);
        fwrite($this->streams[$streamID], '<?php return ' . var_export($config, true) . ';');
        fclose($this->streams['config']);
    }
}