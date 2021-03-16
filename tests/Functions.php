<?php declare(strict_types=1);


namespace rikmeijer\Bootstrap\tests;


final class Functions
{
    private array $streams;

    private array $preparedConfig = [];

    public function __construct(private string $root)
    {
        $this->streams['config'] = fopen($this->root . DIRECTORY_SEPARATOR . 'config.php', 'wb');
    }

    public function __destruct()
    {
        fclose($this->streams['config']);
    }

    public function prepareConfig(string $streamID, array $config): void
    {
        $this->preparedConfig[$streamID] = $config;
    }

    public function createConfig(string $streamID, array $config): void
    {
        if (array_key_exists($streamID, $this->preparedConfig)) {
            $config = array_merge($config, $this->preparedConfig[$streamID]);
        }
        ftruncate($this->streams[$streamID], 0);
        fwrite($this->streams[$streamID], '<?php return ' . var_export($config, true) . ';');
        putenv('BOOTSTRAP_CONFIGURATION_PATH=' . $this->root);
    }
}