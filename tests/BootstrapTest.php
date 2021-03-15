<?php /** @noinspection PhpIncludeInspection */
declare(strict_types=1);

namespace rikmeijer\Bootstrap\tests;

use Closure;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use rikmeijer\Bootstrap\Bootstrap;
use rikmeijer\Bootstrap\Configuration;
use rikmeijer\Bootstrap\PHP;
use function fread;
use function fwrite;

final class BootstrapTest extends TestCase
{
    private array $streams;

    /**
     * @dataProvider optionsProvider
     */
    public function test_WhenSimpleOptionWithDefaultValue_ExpectDefaultValueToBeAvailableInConfiguration(string $function, mixed $configValue): void
    {
        // Assert
        self::assertEquals($configValue, $this->test_WhenOptionWithDefaultValue_ExpectDefaultValueToBeAvailableInConfiguration($function, $configValue));
    }

    private function test_WhenOptionWithDefaultValue_ExpectDefaultValueToBeAvailableInConfiguration(string $function, mixed $configValue): mixed
    {
        // Arrange
        $this->createConfig('config', ['resource' => []]);
        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        $schema = ["option" => $function($configValue)];

        // Act
        return Configuration::validate($schema, $this->getConfigurationRoot(), 'resource')['option'];
    }

    private array $preparedConfig = [];

    private function prepareConfig(string $streamID, array $config): void
    {
        $this->preparedConfig[$streamID] = $config;
    }

    private function createConfig(string $streamID, array $config): void
    {
        if (array_key_exists($streamID, $this->preparedConfig)) {
            $config = array_merge($config, $this->preparedConfig[$streamID]);
        }
        ftruncate($this->streams[$streamID], 0);
        fwrite($this->streams[$streamID], '<?php return ' . var_export($config, true) . ';');
    }

    private function getConfigurationRoot(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->getTestName();
    }

    private function getTestName(): string
    {
        return $this->getName(false);
    }

    private function activateBootstrap(): void
    {
        include $this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap.php';
    }

    /**
     * @dataProvider optionsProvider
     */
    public function testConfig_WhenSimpleOptionRequired_Expect_ErrorWhenNotSupplied(string $function): void
    {
        $this->test_When_OptionRequired_Expect_ErrorWhenNotSupplied($function);
    }

    private function test_When_OptionRequired_Expect_ErrorWhenNotSupplied(string $function): void
    {
        // Arrange
        $this->createConfig('config', ['resource' => []]);
        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        $schema = ["option" => $function()];

        // Assert
        $this->expectError();
        $this->expectErrorMessage('option is not set and has no default value');
        Configuration::validate($schema, $this->getConfigurationRoot(), 'resource');
    }

    /**
     * @dataProvider optionsProvider
     */
    public function testConfig_WhenSimpleOptionRequired_Expect_NoErrorWhenSupplied(string $function, mixed $configValue): void
    {
        self::assertEquals($configValue, $this->testConfig_WhenOptionRequired_Expect_NoErrorWhenSupplied($function, $configValue));
    }


    /**
     * @dataProvider optionsProvider
     */
    public function testConfig_WhenSimpleOptionOptional_Expect_ConfiguredValueOverDefaultValue(string $function, mixed $configValue, mixed $defaultValue): void
    {
        self::assertEquals($configValue, $this->testConfig_WhenOptionOptional_Expect_ConfiguredValuePreferredOverDefaultValue($function, $configValue, $defaultValue));
    }

    private function testConfig_WhenOptionRequired_Expect_NoErrorWhenSupplied(string $function, mixed $configValue): mixed
    {
        // Arrange
        $this->createConfig('config', ['resource' => ['option' => $configValue]]);
        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        $schema = ["option" => $function()];

        // Act
        return Configuration::validate($schema, $this->getConfigurationRoot(), 'resource')['option'];
    }


    private function testConfig_WhenOptionOptional_Expect_ConfiguredValuePreferredOverDefaultValue(string $function, mixed $configValue, mixed ...$defaultValue): mixed
    {
        // Arrange
        self::assertNotEquals($configValue, $defaultValue);

        $this->createConfig('config', ['resource' => ['option' => $configValue]]);
        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        $schema = ["option" => $function(...$defaultValue)];

        // Act
        return Configuration::validate($schema, $this->getConfigurationRoot(), 'resource')['option'];
    }

    public function optionsProvider(): array
    {
        return [
            "boolean" => [
                '\rikmeijer\Bootstrap\configuration\boolean',
                true,
                false
            ],
            "integer" => [
                '\rikmeijer\Bootstrap\configuration\integer',
                1,
                2
            ],
            "float"   => [
                '\rikmeijer\Bootstrap\configuration\float',
                3.14,
                1.34
            ],
            "string"  => [
                '\rikmeijer\Bootstrap\configuration\string',
                "sometext",
                'anytext'
            ],
            "array"   => [
                '\rikmeijer\Bootstrap\configuration\arr',
                [
                    "some",
                    "value"
                ],
                [
                    "any",
                    "value"
                ]
            ]
        ];
    }

    public function test_When_PathOptionWithRelativeDefaultValue_Expect_AbsoluteDefaultValueToBeAvailableInConfiguration(): void
    {
        $path = $this->mkdir('somedir');
        $actual = $this->test_WhenOptionWithDefaultValue_ExpectDefaultValueToBeAvailableInConfiguration('\rikmeijer\Bootstrap\configuration\path', 'somedir');
        self::assertEquals(fileinode($path), fileinode($actual));
    }

    public function test_When_PathOptionWithRelativeDefaultValueWithSubdirectories_Expect_JoinedAbsoluteDefaultValueToBeAvailableInConfiguration(): void
    {
        $path = $this->mkdir('somedir/somesubdir');
        $actual = $this->test_WhenOptionWithDefaultValue_ExpectDefaultValueToBeAvailableInConfiguration('\rikmeijer\Bootstrap\configuration\path', 'somedir/somesubdir');
        self::assertEquals(fileinode($path), fileinode($actual));
    }

    public function test_When_PathOptionConfigurationContainsRelativePath_Expect_AbsolutePathOfConfiguration(): void
    {
        $path = $this->mkdir('somefolder');
        $actual = $this->testConfig_WhenOptionOptional_Expect_ConfiguredValuePreferredOverDefaultValue('\rikmeijer\Bootstrap\configuration\path', 'somefolder', 'somedir');
        self::assertEquals(fileinode($path), fileinode($actual));
    }

    public function testWhenConfigurationRequiresPath_Expect_ErrorWhenNonSupplied(): void
    {
        $this->test_When_OptionRequired_Expect_ErrorWhenNotSupplied('\rikmeijer\Bootstrap\configuration\path');
    }

    public function test_WhenFileOptionWithDefaultValue_ExpectDefaultValueToBeAvailableInConfiguration(): void
    {
        file_put_contents(implode(DIRECTORY_SEPARATOR, [$this->getConfigurationRoot(), 'somefile.txt']), 'Hello World');
        $actual = $this->test_WhenOptionWithDefaultValue_ExpectDefaultValueToBeAvailableInConfiguration('\rikmeijer\Bootstrap\configuration\file', 'somefile.txt');
        self::assertEquals('Hello World', fread($actual("rb"), 11));
    }

    public function test_WhenFileOptionRequired_Expect_ErrorWhenNotSupplied(): void
    {
        $this->test_When_OptionRequired_Expect_ErrorWhenNotSupplied('\rikmeijer\Bootstrap\configuration\file');
    }

    public function test_WhenFileOptionRequired_Expect_NoErrorWhenSupplied(): void
    {
        file_put_contents(implode(DIRECTORY_SEPARATOR, [$this->getConfigurationRoot(), 'somefile.txt']), 'Hello World');
        $actual = $this->testConfig_WhenOptionRequired_Expect_NoErrorWhenSupplied('\rikmeijer\Bootstrap\configuration\file', 'somefile.txt');
        self::assertIsCallable($actual);
        self::assertEquals('Hello World', fread($actual("rb"), 11));
    }

    public function test_WhenFileOptionOptional_Expect_ConfiguredValuePreferredOverDefaultValue(): void
    {
        file_put_contents(implode(DIRECTORY_SEPARATOR, [$this->getConfigurationRoot(), 'somefile.txt']), 'Hello World');
        $actual = $this->testConfig_WhenOptionOptional_Expect_ConfiguredValuePreferredOverDefaultValue('\rikmeijer\Bootstrap\configuration\file', 'somefile.txt', 'anyfile.txt');
        self::assertIsCallable($actual);
        self::assertEquals('Hello World', fread($actual("rb"), 11));
    }

    public function test_When_FileOptionWithPHPoutput_Expect_FunctionToOpenWritableFilestreamAndOutputPrinted(): void
    {
        $actual = $this->test_WhenOptionWithDefaultValue_ExpectDefaultValueToBeAvailableInConfiguration('\rikmeijer\Bootstrap\configuration\file', "php://output");
        self::assertIsCallable($actual);
        $this->expectOutputString('Hello World');
        self::assertEquals(11, fwrite($actual("wb"), "Hello World"));
    }

    public function testWhen_ConfigurationOptionIsBinary_Expect_FunctionToExecuteBinaryAndReturnExitCode(): void
    {
        $command = match (PHP_OS_FAMILY) {
            'Windows' => [
                'c:\\windows\\system32\\cmd.exe',
                '/C',
                "echo test"
            ],
            default => [
                '/usr/bin/bash',
                '-c',
                "echo test"
            ],
        };

        $actual = $this->testConfig_WhenOptionOptional_Expect_ConfiguredValuePreferredOverDefaultValue('\rikmeijer\Bootstrap\configuration\binary', $command, "/usr/bin/bash", "-c", "echo test");

        $this->expectOutputString("Testing test test..." . PHP_EOL . 'test' . PHP_EOL);
        self::assertEquals(0, $actual("Testing test test..."));
    }

    public function testWhen_ConfigurationOptionIsBinaryAndNamedArgumentsAreConfigured_Expect_OnlyThoseToBeReplaced(): void
    {
        $command = match (PHP_OS_FAMILY) {
            'Windows' => [
                'c:\\windows\\system32\\cmd.exe',
                '/C',
                'cmd' => "echo test"
            ],
            default => [
                '/usr/bin/bash',
                '-c',
                'cmd' => "echo test"
            ],
        };

        $actual = $this->testConfig_WhenOptionOptional_Expect_ConfiguredValuePreferredOverDefaultValue('\rikmeijer\Bootstrap\configuration\binary', $command, "/usr/bin/bash", "-c", cmd: "echo test");

        $this->expectOutputString("Testing test test..." . PHP_EOL . 'test4' . PHP_EOL);
        self::assertEquals(0, $actual("Testing test test...", cmd: "echo test4"));
    }

    /**
     * @runInSeparateProcess
     */
    public function testWhen_BinarySimulation_Expect_BinaryNotReallyBeExecuted(): void
    {
        $command = match (PHP_OS_FAMILY) {
            'Windows' => [
                'c:\\windows\\system32\\cmd.exe',
                '/C',
                "echo test"
            ],
            default => [
                '/usr/bin/bash',
                '-c',
                "echo test"
            ],
        };

        $this->prepareConfig('config', [
            'configuration/binary' => ['simulation' => true]
        ]);
        $actual = $this->testConfig_WhenOptionOptional_Expect_ConfiguredValuePreferredOverDefaultValue('\rikmeijer\Bootstrap\configuration\binary', $command, "/usr/bin/bash", "-c", "echo test");

        $this->expectOutputString("What is this?..." . PHP_EOL . '(s) ' . escapeshellcmd($command[0]) . ' ' . $command[1] . ' ' . escapeshellarg($command[2]));
        self::assertEquals(0, $actual("What is this?..."));
    }

    public function testWhen_ConfigurationOptionIsRequiredAndBinary_Expect_ErrorNoneConfigured(): void
    {
        $this->test_When_OptionRequired_Expect_ErrorWhenNotSupplied('\rikmeijer\Bootstrap\configuration\binary');
    }

    private function getFQFN(string $function): string
    {
        return $this->getBootstrapFQFN($this->getTestName() . '\\' . $function);
    }

    private function getBootstrapFQFN(string $function): string
    {
        return '\\rikmeijer\\Bootstrap\\' . $function;
    }

    private function createFunction(string $resourceName, string $content): string
    {
        $directory = dirname($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . $resourceName);
        if (str_contains($resourceName, '/')) {
            is_dir($directory) || mkdir($directory, 0777, true);
        }
        $file = $this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . $resourceName . '.php';
        file_put_contents($file, $content);

        $context = PHP::deductContextFromFile($file);
        return (array_key_exists('namespace', $context) ? '\\' . $context['namespace'] . '\\' . $resourceName : $this->getFQFN($resourceName));
    }

    public function test_When_CustomOptionsAreConfigured_Expect_IgnoredIfNotInSchema(): void
    {
        $this->createConfig('config', ['resource' => ['option2' => "custom"]]);
        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();
        $schema = ["option" => ('rikmeijer\Bootstrap\configuration\string')("default")];

        $configuration = Configuration::validate($schema, $this->getConfigurationRoot(), 'resource');

        self::assertEquals("default", $configuration['option']);
        self::assertArrayNotHasKey('option2', $configuration);
    }

    public function testResource(): void
    {
        $f = $this->createFunction('resource', '<?php ' . PHP_EOL . 'return static function() {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertEquals('Yes!', $f()->status);
    }

    public function test_When_FunctionsNotGenerated_Expect_FunctionsNotExisting(): void
    {
        $f = $this->createFunction('resourceFunc', '<?php ' . PHP_EOL . 'return static function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');

        $this->expectError();
        $this->activateBootstrap();
        self::assertFalse(function_exists($f));
    }

    public function testWhen_VoidCalled_Expect_FunctionNotReturning(): void
    {
        $f = $this->createFunction('resourceFuncVoid', '<?php namespace rikmeijer\\Bootstrap\\fvoid; return static function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) : void {' . PHP_EOL . ' ' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();
        $args = [
            'foo',
            null,
            $this->createMock(ReflectionFunction::class),
            3.14
        ];

        self::assertNull($f(...$args));
    }

    public function testWhen_Called_Expect_FunctionAvailableAsFunctionUnderNS(): void
    {
        $f = '\\rikmeijer\\Bootstrap\\f\\test\\resourceFunc';

        $this->createConfig('config', ['BOOTSTRAP' => ['namespace' => 'rikmeijer\\Bootstrap\\f']]);
        $this->createFunction('test/resourceFunc', '<?php return static function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4 = 0) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();
        $args = [
            'foo',
            null,
            $this->createMock(ReflectionFunction::class)
        ];
        self::assertEquals('Yes!', $f(...$args)->status);
    }

    public function testWhen_CalledDeeper_Expect_FunctionAvailableAsFunctionUnderNS(): void
    {
        $f = '\\rikmeijer\\Bootstrap\\testWhen_CalledDeeper_Expect_FunctionAvailableAsFunctionUnderNS\\test\\test\\resourceFunc';

        $this->createFunction('test/test/resourceFunc', '<?php return static function($arg1, ?string $arg2, \ReflectionFunction $arg3, string $arg4 = "") {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();
        $args = [
            'foo',
            null,
            $this->createMock(ReflectionFunction::class)
        ];
        self::assertEquals('Yes!', $f(...$args)->status);
    }

    public function testWhen_ResourcesAreGenerated_Expect_ResourcesAvailableAsFunctions(): void
    {
        $f0 = '\\rikmeijer\\Bootstrap\\f\\resourceFunc';
        $f1 = '\\rikmeijer\\Bootstrap\\f\\test\\resourceFunc';
        $f2 = '\\rikmeijer\\Bootstrap\\f\\test\\test\\resourceFunc';

        $this->createConfig('config', [
            'BOOTSTRAP'              => ['namespace' => 'rikmeijer\\Bootstrap\\f'],
            'test/test/resourceFunc' => ['status' => 'Yesss!']
        ]);
        $this->createFunction('resourceFunc', '<?php return ' . $f0 . '\\configure(function(array $configuration, $arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '}, []);');
        $this->createFunction('test/resourceFunc', '<?php return ' . $f1 . '\\configure(function(array $configuration, $arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '}, []);');
        $this->createFunction('test/test/resourceFunc', '<?php return ' . $f2 . '\\configure(function(array $configuration, $arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) {' . PHP_EOL . '   return (object)["status" => $configuration["status"]];' . PHP_EOL . '}, []);');

        Bootstrap::generate($this->getConfigurationRoot());

        self::assertFileExists($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap.php');

        $this->activateBootstrap();
        $args = [
            'foo',
            null,
            $this->createMock(ReflectionFunction::class),
            3.14
        ];

        self::assertEquals('Yes!', $f0(...$args)->status);
        self::assertEquals('Yes!', $f1(...$args)->status);
        self::assertEquals('Yesss!', $f2(...$args)->status);
    }

    public function testWhen_ResourcesAreGeneratedWithinNS_Expect_ResourceConfigsAvailableAsFunctions(): void
    {
        $f0 = '\\rikmeijer\\Bootstrap\\f2\\resourceFunc';
        $f1 = '\\rikmeijer\\Bootstrap\\f2\\test\\resourceFunc';
        $f2 = '\\rikmeijer\\Bootstrap\\f2\\test\\test\\resourceFunc';

        $this->createConfig('config', ['test/test/resourceFunc' => ['status' => 'Yesss!']]);
        $this->createFunction('resourceFunc', '<?php namespace rikmeijer\\Bootstrap\\f2; return ' . $f0 . '\\configure(function(array $configuration, $arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '}, []);');
        $this->createFunction('test/resourceFunc', '<?php namespace rikmeijer\\Bootstrap\\f2\\test; return ' . $f1 . '\\configure(function(array $configuration, $arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '}, []);');
        $this->createFunction('test/test/resourceFunc', '<?php namespace rikmeijer\\Bootstrap\\f2\\test\\test; return ' . $f2 . '\\configure(function(array $configuration, $arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) {' . PHP_EOL . '   return (object)["status" => $configuration["status"]];' . PHP_EOL . '}, []);');

        Bootstrap::generate($this->getConfigurationRoot());

        self::assertFileExists($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap.php');

        $this->activateBootstrap();
        $args = [
            'foo',
            null,
            $this->createMock(ReflectionFunction::class),
            3.14
        ];

        self::assertEquals('Yes!', $f0(...$args)->status);
        self::assertEquals('Yes!', $f1(...$args)->status);
        self::assertEquals('Yesss!', $f2(...$args)->status);
    }

    public function testResourceWhenExtraArgumentsArePassed_Expect_ParametersAvailable(): void
    {
        $f = $this->createFunction('resource', '<?php return function(string $extratext) { ' . PHP_EOL . '   return (object)["status" => "Yes!" . $extratext]; };');
        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertEquals('Yes!Hello World', $f('Hello World')->status);
    }

    public function testWhenConfigurationSectionMatchesResourcesName_ExpectConfigurationToBePassedToBootstrapper(): void
    {
        $value = uniqid('', true);
        $f = $this->createFunction('resource', '<?php namespace ' . substr($this->getBootstrapFQFN($this->getTestName()), 1) . '; return resource\\configure(function(array $configuration) { ' . PHP_EOL . '    return (object)["status" => $configuration["status"]]; ' . PHP_EOL . '}, ["status" => ' . $this->getBootstrapFQFN('configuration\\string') . '("' . $value . '")]);');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertEquals($value, $f()->status);
    }

    private function mkdir(string $path): string
    {
        $path = $this->getConfigurationRoot() . DIRECTORY_SEPARATOR . $path;
        if (!is_dir($path) && !mkdir($path, recursive: true)) {
            trigger_error("Unable to create " . $path, E_USER_ERROR);
        }
        return $path;
    }

    public function testWhenResourceDependentOfOtherResource_Expect_ResourcesVariableCallableAndReturningDependency(): void
    {
        $value = uniqid('', true);

        $dependency = $this->createFunction('dependency', '<?php namespace ' . substr($this->getBootstrapFQFN($this->getTestName()), 1) . ';  return dependency\\configure(function(array $configuration) : object {' . PHP_EOL . '   return (object)["status" => $configuration["status"]]; ' . PHP_EOL . '}, ["status" => ' . $this->getBootstrapFQFN('configuration\\string') . '("' . $value . '")]);');

        $f = $this->createFunction('resourceDependent', '<?php ' . PHP_EOL . 'return function() { ' . PHP_EOL . '   return (object)["status" => ' . $dependency . '()->status]; ' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertEquals($value, $f()->status);
    }

    public function testWhenResourceDependentOfOtherResourceWithExtraArguments_Expect_ExtraParametersAvailableInDependency(): void
    {
        $value = uniqid('', true);

        $dependency = $this->createFunction('dependency', '<?php namespace ' . substr($this->getBootstrapFQFN($this->getTestName()), 1) . '; return dependency\\configure(function(array $configuration, string $extratext) : object { ' . PHP_EOL . '   return (object)["status" => $configuration["status"] . $extratext]; ' . PHP_EOL . '}, ["status" => ' . $this->getBootstrapFQFN('configuration\\string') . '("' . $value . '")]);');
        $f = $this->createFunction('resourceDependent', '<?php' . PHP_EOL . 'return function() {' . PHP_EOL . '   return (object)["status" => ' . $dependency . '("Hello World!")->status]; ' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertEquals($value . 'Hello World!', $f()->status);
    }

    public function testWhenNoConfigurationIsRequired_ExpectOnlyDependenciesInjectedByBootstrap(): void
    {
        $value = uniqid('', true);

        $dependency2 = $this->createFunction('dependency2', '<?php namespace ' . substr($this->getBootstrapFQFN($this->getTestName()), 1) . '; return dependency2\\configure(function(array $configuration) : object { ' . PHP_EOL . '   return (object)["status" => $configuration["status"]]; ' . PHP_EOL . '}, ["status" => ' . $this->getBootstrapFQFN('configuration\\string') . '("' . $value . '")]);');

        $f = $this->createFunction('resourceDependent2', '<?php ' . PHP_EOL . 'return function() { ' . PHP_EOL . '   return (object)["status" => ' . $dependency2 . '()->status]; ' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertEquals($value, $f()->status);
    }

    public function testResourceCache(): void
    {
        $f = $this->createFunction('resourceCache', '<?php ' . PHP_EOL . 'return function() { ' . PHP_EOL . '   return (object)["status" => "Yes!"]; ' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertEquals('Yes!', $f()->status);

        $this->createFunction('resourceCache', '<?php ' . PHP_EOL . 'return function() { ' . PHP_EOL . '   return (object)["status" => "No!"];' . PHP_EOL . '};');
        self::assertNotInstanceOf(Closure::class, $f());
        self::assertEquals('Yes!', $f()->status);

    }

    protected function setUp(): void
    {
        $this->mkdir('bootstrap');
        $this->streams['config'] = fopen($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'config.php', 'wb');
    }

    protected function tearDown(): void
    {
        fclose($this->streams['config']);
        $this->deleteDirRecursively($this->getConfigurationRoot());
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

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function exposeGeneratedFunctions(): void
    {
        print file_get_contents($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap.php');
    }
}
