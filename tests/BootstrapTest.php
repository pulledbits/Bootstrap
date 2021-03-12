<?php /** @noinspection PhpIncludeInspection */
declare(strict_types=1);

namespace rikmeijer\Bootstrap\tests;

use Closure;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use rikmeijer\Bootstrap\Bootstrap;
use rikmeijer\Bootstrap\Configuration;
use Webmozart\PathUtil\Path;
use function fread;
use function fwrite;

final class BootstrapTest extends TestCase
{
    private array $streams;
    private array $createdDirectories = [];

    /**
     * @dataProvider optionsProvider
     */
    public function test_WhenSimpleOptionWithDefaultValue_ExpectDefaultValueToBeAvailableInConfiguration(string $function, mixed $configValue): void
    {
        // Assert
        self::assertEquals($configValue, $this->test_WhenOptionWithDefaultValue_ExpectDefaultValueToBeAvailableInConfiguration($function, $configValue));
    }

    private function createConfig(string $streamID, array $config): void
    {
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
        $this->testConfig_WhenOptionRequired_Expect_ErrorWhenNotSupplied($function);
    }

    private function testConfig_WhenOptionRequired_Expect_ErrorWhenNotSupplied(string $function): void
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

    public function optionsProvider(): array
    {
        return [
            "boolean" => [
                '\rikmeijer\Bootstrap\configuration\boolean',
                true
            ],
            "integer" => [
                '\rikmeijer\Bootstrap\configuration\integer',
                1
            ],
            "float"   => [
                '\rikmeijer\Bootstrap\configuration\float',
                3.14
            ],
            "string"  => [
                '\rikmeijer\Bootstrap\configuration\string',
                "sometext"
            ],
            "array"   => [
                '\rikmeijer\Bootstrap\configuration\arr',
                [
                    "some",
                    "value"
                ]
            ]
        ];
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

    private function getFQFN(string $function): string
    {
        return $this->getBootstrapFQFN($this->getTestName() . '\\' . $function);
    }

    private function getBootstrapFQFN(string $function): string
    {
        return '\\rikmeijer\\Bootstrap\\' . $function;
    }

    private function createFunction(string $resourceName, string $content): void
    {
        $directory = dirname($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . $resourceName);
        if (str_contains($resourceName, '/')) {
            is_dir($directory) || mkdir($directory, 0777, true);
        }
        file_put_contents($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . $resourceName . '.php', $content);
    }


    public function test_WhenFileOptionWithDefaultValue_ExpectDefaultValueToBeAvailableInConfiguration(): void
    {
        file_put_contents(Path::join($this->getConfigurationRoot(), 'somefile.txt'), 'Hello World');
        $actual = $this->test_WhenOptionWithDefaultValue_ExpectDefaultValueToBeAvailableInConfiguration('\rikmeijer\Bootstrap\configuration\file', 'somefile.txt');
        self::assertEquals('Hello World', fread($actual("rb"), 11));
    }

    public function test_WhenFileOptionRequired_Expect_ErrorWhenNotSupplied(): void
    {
        $this->testConfig_WhenOptionRequired_Expect_ErrorWhenNotSupplied('\rikmeijer\Bootstrap\configuration\file');
    }

    public function test_WhenFileOptionRequired_Expect_NoErrorWhenSupplied(): void
    {
        file_put_contents(Path::join($this->getConfigurationRoot(), 'somefile.txt'), 'Hello World');
        $actual = $this->testConfig_WhenOptionRequired_Expect_NoErrorWhenSupplied('\rikmeijer\Bootstrap\configuration\file', 'somefile.txt');
        self::assertIsCallable($actual);
        self::assertEquals('Hello World', fread($actual("rb"), 11));
    }


    public function testWhen_ConfigurationOptionIsFileWithPHPoutput_Expect_FunctionToOpenWritableFilestream(): void
    {
        $actual = $this->test_WhenOptionWithDefaultValue_ExpectDefaultValueToBeAvailableInConfiguration('\rikmeijer\Bootstrap\configuration\file', "php://output");
        self::assertIsCallable($actual);
        $this->expectOutputString('Hello World');
        self::assertEquals(11, fwrite($actual("wb"), "Hello World"));
    }

    public function testWhen_ConfigurationOptionIsBinaryAndNamedArgumentsAreConfigured_Expect_OnlyThoseToBeReplaced(): void
    {
        $f = $this->getFQFN('resource');
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

        $this->createConfig('config', ['resource' => ['binary' => $command]]);
        $this->createFunction('resource', '<?php return ' . $f . '\\configure(function(array $configuration) : void { ' . PHP_EOL . '$configuration["binary"]("Testing test test...", cmd : "echo test4"); ' . PHP_EOL . '}, ["binary" => ' . $this->getBootstrapFQFN('configuration\\binary') . '("/usr/bin/bash", "-c", cmd : "echo test")]);');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        $this->expectOutputString("Testing test test..." . PHP_EOL . 'test4' . PHP_EOL);
        $f();
    }

    public function testWhen_ConfigurationOptionIsBinary_Expect_FunctionToExecuteBinaryAndReturnExitCode(): void
    {
        $f = $this->getFQFN('resource');
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

        $this->createConfig('config', ['resource' => ['binary' => $command]]);
        $this->createFunction('resource', '<?php return ' . $f . '\\configure(function(array $configuration) : int { ' . PHP_EOL . 'return $configuration["binary"]("Testing test test..."); ' . PHP_EOL . '}, ["binary" => ' . $this->getBootstrapFQFN('configuration\\binary') . '("/usr/bin/bash", "-c", "echo test")]);');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        $this->expectOutputString("Testing test test..." . PHP_EOL . 'test' . PHP_EOL);
        self::assertEquals(0, $f());
    }

    /**
     * @runInSeparateProcess
     */
    public function testWhen_BinarySimulation_Expect_BinaryNotReallyBeExecuted(): void
    {
        $f = $this->getFQFN('resource');
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

        $this->createConfig('config', [
            'configuration/binary' => ['simulation' => true],
            'resource'             => ['binary' => $command]
        ]);
        $this->createFunction('resource', '<?php return ' . $f . '\\configure(function(array $configuration) : void { ' . PHP_EOL . ' $configuration["binary"]("What is this?..."); ' . PHP_EOL . '}, ["binary" => ' . $this->getBootstrapFQFN('configuration\\binary') . '("/usr/bin/bash", "-c", "echo test")]);');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        $this->expectOutputString("What is this?..." . PHP_EOL . '(s) ' . escapeshellcmd($command[0]) . ' ' . $command[1] . ' ' . escapeshellarg($command[2]));
        $f();
    }

    public function testWhen_ConfigurationOptionIsRequiredAndBinary_Expect_ErrorNoneConfigured(): void
    {
        $f = $this->getFQFN('resource');
        $this->createFunction('resource', '<?php return ' . $f . '\\configure(function(array $configuration) { ' . PHP_EOL . '$out = ""; foreach($configuration["binary"]() as $line) { $out = $line; } return (object)["file" => $out]; ' . PHP_EOL . '}, ["binary" => ' . $this->getBootstrapFQFN('configuration\\binary') . '()]);');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        $this->expectError();
        $this->expectErrorMessage('binary is not set and has no default value');
        $f();
    }

    public function testConfig_CustomOption(): void
    {
        $value = uniqid('', true);
        $value2 = uniqid('', true);
        $f = $this->getFQFN('resourceCustom');

        $this->createConfig('config', ["resourceCustom" => ["option" => $value2]]);
        $this->createFunction('resourceCustom', '<?php ' . PHP_EOL . 'return ' . $f . '\\configure(function(array $configuration) {' . PHP_EOL . '   return (object)["option" => $configuration["option"]];' . PHP_EOL . '}, ["option" => ' . $this->getBootstrapFQFN('configuration\\string') . '("' . $value . '")]);');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertEquals($value2, $f()->option);
    }

    public function testConfig_CustomOption_RecursiveMerge(): void
    {
        $value = uniqid('', true);
        $f = $this->getFQFN('resource');

        $this->createConfig('config', ["resource" => ["option2" => "custom"]]);
        $this->createFunction('resource', '<?php ' . PHP_EOL . 'return ' . $f . '\\configure(function(array $configuration) { ' . PHP_EOL . '   return (object)["option" => $configuration["option1"]];' . PHP_EOL . '}, ["option1" => ' . $this->getBootstrapFQFN('configuration\\string') . '("' . $value . '")]);');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertEquals($value, $f()->option);
    }

    public function testResource(): void
    {
        $f = $this->getFQFN('resource');
        $this->createFunction('resource', '<?php ' . PHP_EOL . 'return static function() {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertEquals('Yes!', $f()->status);
    }

    public function testWhen_UsingConfiguration_Expect_DedicatedValidateFunctionAvailable(): void
    {
        $f = '\\my\\ns\\resource';
        $this->createFunction('resource', '<?php namespace my\ns;' . PHP_EOL . 'return static function() {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertEquals('Yes!', $f()->status);
    }

    public function testWhen_FunctionsFileMissing_Expect_FunctionsNotExistingButNoError(): void
    {
        $this->createFunction('resourceFunc', '<?php ' . PHP_EOL . 'return static function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');

        $this->expectError();
        $this->activateBootstrap();
        self::assertFalse(function_exists($this->getFQFN('resourceFunc')));
    }

    public function testWhen_VoidCalled_Expect_FunctionNotReturning(): void
    {
        $f = '\\rikmeijer\\Bootstrap\\fvoid\\resourceFuncVoid';

        $this->createConfig('config', ['BOOTSTRAP' => ['namespace' => 'rikmeijer\\Bootstrap\\fvoid']]);
        $this->createFunction('resourceFuncVoid', '<?php return static function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) : void {' . PHP_EOL . ' ' . PHP_EOL . '};');

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

    public function testWhen_Called_Expect_FunctionAvailableAsFunction(): void
    {
        $f = '\\rikmeijer\\Bootstrap\\f\\resourceFunc';

        $this->createConfig('config', ['BOOTSTRAP' => ['namespace' => 'rikmeijer\\Bootstrap\\f']]);
        $this->createFunction('resourceFunc', '<?php return static function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();
        $args = [
            'foo',
            null,
            $this->createMock(ReflectionFunction::class),
            3.14
        ];
        self::assertEquals('Yes!', $f(...$args)->status);
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
        $f = $this->getFQFN('resource');
        $this->createFunction('resource', '<?php return function(string $extratext) { ' . PHP_EOL . '   return (object)["status" => "Yes!" . $extratext]; };');
        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertEquals('Yes!Hello World', $f('Hello World')->status);
    }

    public function testWhenConfigurationSectionMatchesResourcesName_ExpectConfigurationToBePassedToBootstrapper(): void
    {
        $value = uniqid('', true);
        $f = $this->getFQFN('resource');

        $this->createFunction('resource', '<?php return ' . $f . '\\configure(function(array $configuration) { ' . PHP_EOL . '    return (object)["status" => $configuration["status"]]; ' . PHP_EOL . '}, ["status" => ' . $this->getBootstrapFQFN('configuration\\string') . '("' . $value . '")]);');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertEquals($value, $f()->status);
    }

    public function testWhenConfigurationRequiresPath_Expect_ErrorWhenNonSupplied(): void
    {
        $this->mkdir(Path::join($this->getConfigurationRoot(), 'somedir'));
        $f = $this->getFQFN('resource');
        $this->createFunction('resource', '<?php return ' . $f . '\\configure(function(array $configuration) { ' . PHP_EOL . '    return (object)["status" => $configuration["optionPath"]];' . PHP_EOL . '}, ["optionPath" => ' . $this->getBootstrapFQFN('configuration\\path') . '()]);');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        $this->expectError();
        $this->expectErrorMessage('optionPath is not set and has no default value');
        $f()->status;
    }

    private function mkdir(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, recursive: true)) {
            trigger_error("Unable to create " . $path);
        } else {
            $this->createdDirectories[] = $path;
        }
    }

    public function testWhenConfigurationMissingPath_ExpectConfigurationWithPathRelativeToConfigurationPath(): void
    {
        $this->mkdir(Path::join($this->getConfigurationRoot(), 'somedir'));
        $f = $this->getFQFN('resource');
        $this->createFunction('resource', '<?php return ' . $f . '\\configure(function(array $configuration) { ' . PHP_EOL . '    return (object)["status" => $configuration["path"]];' . PHP_EOL . '}, ["path" => ' . $this->getBootstrapFQFN('configuration\\path') . '("somedir")]);');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertEquals(fileinode(Path::join($this->getConfigurationRoot(), 'somedir')), fileinode($f()->status));
    }

    public function testWhenConfigurationMissingPatheWithSubdirs_ExpectJoinedAbsolutePath(): void
    {
        $this->mkdir(Path::join($this->getConfigurationRoot(), 'somedir', 'somesubdir'));
        $f = $this->getFQFN('resource');
        $this->createFunction('resource', '<?php return ' . $f . '\\configure(function(array $configuration) { ' . PHP_EOL . '    return (object)["status" => $configuration["path"]]; ' . PHP_EOL . '}, ["path" => ' . $this->getBootstrapFQFN('configuration\\path') . '("somedir", "somesubdir")]);');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertEquals(fileinode(Path::join($this->getConfigurationRoot(), 'somedir', 'somesubdir')), fileinode($f()->status));
    }

    public function testWhenConfigurationRelativePath_ExpectAbsolutePath(): void
    {
        $this->mkdir(Path::join($this->getConfigurationRoot(), 'somefolder'));
        $f = $this->getFQFN('resource');
        $this->createConfig('config', ["resource" => ["path" => "somefolder"]]);
        $this->createFunction('resource', '<?php return ' . $f . '\\configure(function(array $configuration) { ' . PHP_EOL . '    return (object)["status" => $configuration["path"]]; ' . PHP_EOL . '}, ["path" => ' . $this->getBootstrapFQFN('configuration\\path') . '()]);');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertEquals(fileinode(Path::join($this->getConfigurationRoot(), 'somefolder')), fileinode($f()->status));
    }

    public function testWhenResourceDependentOfOtherResource_Expect_ResourcesVariableCallableAndReturningDependency(): void
    {
        $value = uniqid('', true);

        $this->createFunction('dependency', '<?php return ' . $this->getFQFN('dependency') . '\\configure(function(array $configuration) : object {' . PHP_EOL . '   return (object)["status" => $configuration["status"]]; ' . PHP_EOL . '}, ["status" => ' . $this->getBootstrapFQFN('configuration\\string') . '("' . $value . '")]);');

        $this->createFunction('resourceDependent', '<?php ' . PHP_EOL . 'return function() { ' . PHP_EOL . '   return (object)["status" => ' . $this->getFQFN('dependency') . '()->status]; ' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        $f = $this->getFQFN('resourceDependent');
        self::assertEquals($value, $f()->status);
    }

    public function testWhenResourceDependentOfOtherResourceWithExtraArguments_Expect_ExtraParametersAvailableInDependency(): void
    {
        $value = uniqid('', true);

        $this->createFunction('dependency', '<?php return ' . $this->getFQFN('dependency') . '\\configure(function(array $configuration, string $extratext) : object { ' . PHP_EOL . '   return (object)["status" => $configuration["status"] . $extratext]; ' . PHP_EOL . '}, ["status" => ' . $this->getBootstrapFQFN('configuration\\string') . '("' . $value . '")]);');
        $this->createFunction('resourceDependent', '<?php' . PHP_EOL . 'return function() {' . PHP_EOL . '   return (object)["status" => ' . $this->getFQFN('dependency') . '("Hello World!")->status]; ' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        $f = $this->getFQFN('resourceDependent');
        self::assertEquals($value . 'Hello World!', $f()->status);
    }

    public function testWhenNoConfigurationIsRequired_ExpectOnlyDependenciesInjectedByBootstrap(): void
    {
        $value = uniqid('', true);

        $this->createFunction('dependency2', '<?php return ' . $this->getFQFN('dependency2') . '\\configure(function(array $configuration) : object { ' . PHP_EOL . '   return (object)["status" => $configuration["status"]]; ' . PHP_EOL . '}, ["status" => ' . $this->getBootstrapFQFN('configuration\\string') . '("' . $value . '")]);');

        $this->createFunction('resourceDependent2', '<?php ' . PHP_EOL . 'return function() { ' . PHP_EOL . '   return (object)["status" => ' . $this->getFQFN('dependency2') . '()->status]; ' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        $f = $this->getFQFN('resourceDependent2');
        self::assertEquals($value, $f()->status);
    }

    public function testResourceCache(): void
    {
        $this->createFunction('resourceCache', '<?php ' . PHP_EOL . 'return function() { ' . PHP_EOL . '   return (object)["status" => "Yes!"]; ' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();
        $f = $this->getFQFN('resourceCache');
        self::assertEquals('Yes!', $f()->status);

        $this->createFunction('resourceCache', '<?php ' . PHP_EOL . 'return function() { ' . PHP_EOL . '   return (object)["status" => "No!"];' . PHP_EOL . '};');
        self::assertNotInstanceOf(Closure::class, $f());
        self::assertEquals('Yes!', $f()->status);

    }

    protected function setUp(): void
    {
        $this->mkdir(Path::join($this->getConfigurationRoot()));
        $this->mkdir(Path::join($this->getConfigurationRoot(), 'bootstrap'));
        $this->streams['config'] = fopen($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'config.php', 'wb');
    }

    protected function tearDown(): void
    {
        fclose($this->streams['config']);
        foreach (array_reverse($this->createdDirectories) as $createdDirectory) {
            $this->deleteDirRecursively($createdDirectory);
        }
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
