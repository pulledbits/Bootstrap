<?php /** @noinspection PhpIncludeInspection */
declare(strict_types=1);

namespace rikmeijer\Bootstrap\tests;

use Closure;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use rikmeijer\Bootstrap\Bootstrap;
use Webmozart\PathUtil\Path;

final class BootstrapTest extends TestCase
{
    private array $streams;

    public function testConfig_DefaultOption(): void
    {
        $f = '\\my\\own\\ns\\resource';

        $this->createFunction('resource', '<?php namespace my\own\ns; ' . PHP_EOL . 'return ' . $f . '\\configure(function(array $configuration) { ' . PHP_EOL . '  return (object)["configuration" => $configuration]; ' . PHP_EOL . '}, [
            "optionBoolean" => ' . $this->getBootstrapFQFN('configuration\\boolean') . '(true),
            "optionInteger" => ' . $this->getBootstrapFQFN('configuration\\integer') . '(1),
            "optionFloat" => ' . $this->getBootstrapFQFN('configuration\\float') . '(3.14),
            "optionString" => ' . $this->getBootstrapFQFN('configuration\\string') . '("text"),
            "optionArray" => ' . $this->getBootstrapFQFN('configuration\\arr') . '(["some", "value"])
            ]);');

        // Act
        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertTrue($f()->configuration["optionBoolean"]);
        self::assertEquals(1, $f()->configuration["optionInteger"]);
        self::assertEquals(3.14, $f()->configuration["optionFloat"]);
        self::assertEquals("text", $f()->configuration["optionString"]);
        self::assertEquals(["some", "value"], $f()->configuration["optionArray"]);
    }

    public function testConfig_WhenOptionBooleanRequired_Expect_ErrorWhenNotSupplied(): void
    {
        $f = $this->getFQFN('resource');
        $this->createFunction('resource', '<?php return ' . $f . '\\configure(function(array $configuration) { ' . PHP_EOL . '  return (object)["configuration" => $configuration]; ' . PHP_EOL . '}, [
            "optionBoolean" => ' . $this->getBootstrapFQFN('configuration\\boolean') . '(null)
            ]);');

        // Act
        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        $this->expectError();
        $this->expectErrorMessage('optionBoolean is not set and has no default value');
        $f()->configuration["optionBoolean"];
    }

    public function testConfig_WhenOptionBooleanRequired_Expect_NoErrorWhenSupplied(): void
    {
        $f = $this->getFQFN('resource');
        $this->createConfig('config', ['resource' => ['optionBoolean' => true]]);
        $this->createFunction('resource', '<?php return ' . $f . '\\configure(function(array $configuration) { ' . PHP_EOL . '  return (object)["configuration" => $configuration]; ' . PHP_EOL . '}, [
            "optionBoolean" => ' . $this->getBootstrapFQFN('configuration\\boolean') . '(null)
            ]);');

        // Act
        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertTrue($f()->configuration["optionBoolean"]);
    }

    public function testConfig_WhenOptionIntegerRequired_Expect_ErrorWhenNotSupplied(): void
    {
        $f = $this->getFQFN('resource');
        $this->createFunction('resource', '<?php return ' . $f . '\\configure(function(array $configuration) { ' . PHP_EOL . '  return (object)["configuration" => $configuration]; ' . PHP_EOL . '}, [
            "optionInteger" => ' . $this->getBootstrapFQFN('configuration\\integer') . '(null)
            ]);');

        // Act
        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        $this->expectError();
        $this->expectErrorMessage('optionInteger is not set and has no default value');
        $f()->configuration["optionInteger"];
    }

    public function testConfig_WhenOptionIntegerRequired_Expect_NoErrorWhenSupplied(): void
    {
        $f = $this->getFQFN('resource');
        $this->createConfig('config', ['resource' => ['optionInteger' => 1]]);
        $this->createFunction('resource', '<?php return ' . $f . '\\configure(function(array $configuration) { ' . PHP_EOL . '  return (object)["configuration" => $configuration]; ' . PHP_EOL . '}, [
            "optionInteger" => ' . $this->getBootstrapFQFN('configuration\\integer') . '(null)
            ]);');

        // Act
        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertEquals(1, $f()->configuration["optionInteger"]);
    }

    public function testConfig_WhenOptionFloatRequired_Expect_ErrorWhenNotSupplied(): void
    {
        $f = $this->getFQFN('resource');
        $this->createFunction('resource', '<?php return ' . $f . '\\configure(function(array $configuration) { ' . PHP_EOL . '  return (object)["configuration" => $configuration]; ' . PHP_EOL . '}, [
            "optionFloat" => ' . $this->getBootstrapFQFN('configuration\\float') . '(null)
            ]);');

        // Act
        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        $this->expectError();
        $this->expectErrorMessage('optionFloat is not set and has no default value');
        $f()->configuration["optionFloat"];
    }

    public function testConfig_WhenOptionFloatRequired_Expect_NoErrorWhenSupplied(): void
    {
        $f = $this->getFQFN('resource');
        $this->createConfig('config', ['resource' => ['optionFloat' => 3.14]]);
        $this->createFunction('resource', '<?php return ' . $f . '\\configure(function(array $configuration) { ' . PHP_EOL . '  return (object)["configuration" => $configuration]; ' . PHP_EOL . '}, [
            "optionFloat" => ' . $this->getBootstrapFQFN('configuration\\float') . '(null)
            ]);');

        // Act
        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertEquals(3.14, $f()->configuration["optionFloat"]);
    }

    public function testConfig_WhenOptionStringRequired_Expect_ErrorWhenNotSupplied(): void
    {
        $f = $this->getFQFN('resource');
        $this->createFunction('resource', '<?php return ' . $f . '\\configure(function(array $configuration) { ' . PHP_EOL . '  return (object)["configuration" => $configuration]; ' . PHP_EOL . '}, [
            "optionString" => ' . $this->getBootstrapFQFN('configuration\\string') . '(null)
            ]);');

        // Act
        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        $this->expectError();
        $this->expectErrorMessage('optionString is not set and has no default value');
        $f()->configuration["optionString"];
    }

    public function testConfig_WhenOptionStringRequired_Expect_NoErrorWhenSupplied(): void
    {
        $f = $this->getFQFN('resource');
        $this->createConfig('config', ['resource' => ['optionString' => "text"]]);
        $this->createFunction('resource', '<?php return ' . $f . '\\configure(function(array $configuration) { ' . PHP_EOL . '  return (object)["configuration" => $configuration]; ' . PHP_EOL . '}, [
            "optionString" => ' . $this->getBootstrapFQFN('configuration\\string') . '(null)
            ]);');

        // Act
        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertEquals("text", $f()->configuration["optionString"]);
    }

    public function testConfig_WhenOptionArrayRequired_Expect_ErrorWhenNotSupplied(): void
    {
        $f = $this->getFQFN('resource');
        $this->createFunction('resource', '<?php return ' . $f . '\\configure(function(array $configuration) { ' . PHP_EOL . '  return (object)["configuration" => $configuration]; ' . PHP_EOL . '}, [
            "optionArray" => ' . $this->getBootstrapFQFN('configuration\\arr') . '(null)
            ]);');

        // Act
        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        $this->expectError();
        $this->expectErrorMessage('optionArray is not set and has no default value');
        $f()->configuration["optionArray"];
    }

    public function testConfig_WhenOptionArrayRequired_Expect_NoErrorWhenSupplied(): void
    {
        $f = $this->getFQFN('resource');
        $this->createConfig('config', ['resource' => ['optionArray' => ["some", "value"]]]);
        $this->createFunction('resource', '<?php return ' . $f . '\\configure(function(array $configuration) { ' . PHP_EOL . '  return (object)["configuration" => $configuration]; ' . PHP_EOL . '}, [
            "optionArray" => ' . $this->getBootstrapFQFN('configuration\\arr') . '(null)
            ]);');

        // Act
        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertEquals(["some", "value"], $f()->configuration["optionArray"]);
    }

    public function testWhen_ConfigurationOptionIsFile_Expect_FunctionToOpenFilestream(): void
    {
        $somefile = Path::join($this->getConfigurationRoot(), 'somefile.txt');
        file_put_contents($somefile, 'Hello World');
        $f = $this->getFQFN('resource');
        $this->createFunction('resource', '<?php return ' . $f . '\\configure(function(array $configuration) { ' . PHP_EOL . '    return (object)["file" => \fread($configuration["file"]("rb"), 11)]; ' . PHP_EOL . '}, ["file" => ' . $this->getBootstrapFQFN('configuration\\file') . '("somefile.txt")]);');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertEquals('Hello World', $f()->file);
    }

    public function testWhen_ConfigurationOptionIsFile_Expect_FunctionToOpenWritableStream(): void
    {
        $somefile = Path::join($this->getConfigurationRoot(), 'somefile.txt');
        $f = $this->getFQFN('resource');
        $this->createFunction('resource', '<?php return ' . $f . '\\configure(function(array $configuration) { ' . PHP_EOL . '    return (object)["file" => \fwrite($configuration["file"]("wb"), "Hello World dus")]; ' . PHP_EOL . '}, ["file" => ' . $this->getBootstrapFQFN('configuration\\file') . '("somefile.txt")]);');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertEquals(15, $f()->file);
        self::assertEquals('Hello World dus', file_get_contents($somefile));
    }

    public function testWhen_ConfigurationOptionIsBinary_Expect_FunctionToExecuteBinary(): void
    {
        $somefile = Path::join($this->getConfigurationRoot(), 'binary.bat');
        file_put_contents($somefile, '@echo off' . PHP_EOL . 'echo arguments: %1 %2');
        $f = $this->getFQFN('resource');
        $this->createConfig('config', ['resource' => ['binary' => $somefile]]);
        $this->createFunction('resource', '<?php return ' . $f . '\\configure(function(array $configuration) { ' . PHP_EOL . '$out = ""; foreach($configuration["binary"]("arg1", "arg2") as $line) { $out = $line; } return (object)["file" => $out]; ' . PHP_EOL . '}, ["binary" => ' . $this->getBootstrapFQFN('configuration\\binary') . '("binary.bat")]);');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();

        self::assertEquals('arguments: "arg1" "arg2"' . PHP_EOL, $f()->file);
    }

    private function createConfig(string $streamID, array $config): void
    {
        ftruncate($this->streams[$streamID], 0);
        fwrite($this->streams[$streamID], '<?php return ' . var_export($config, true) . ';');
    }

    private function createFunction(string $resourceName, string $content): void
    {
        $directory = dirname($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . $resourceName);
        if (str_contains($resourceName, '/')) {
            is_dir($directory) || mkdir($directory, 0777, true);
        }
        file_put_contents($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . $resourceName . '.php', $content);
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
        $args = ['foo', null, $this->createMock(ReflectionFunction::class), 3.14];

        self::assertNull($f(...$args));
    }

    public function testWhen_Called_Expect_FunctionAvailableAsFunction(): void
    {
        $f = '\\rikmeijer\\Bootstrap\\f\\resourceFunc';

        $this->createConfig('config', ['BOOTSTRAP' => ['namespace' => 'rikmeijer\\Bootstrap\\f']]);
        $this->createFunction('resourceFunc', '<?php return static function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();
        $args = ['foo', null, $this->createMock(ReflectionFunction::class), 3.14];
        self::assertEquals('Yes!', $f(...$args)->status);
    }

    public function testWhen_Called_Expect_FunctionAvailableAsFunctionUnderNS(): void
    {
        $f = '\\rikmeijer\\Bootstrap\\f\\test\\resourceFunc';

        $this->createConfig('config', ['BOOTSTRAP' => ['namespace' => 'rikmeijer\\Bootstrap\\f']]);
        $this->createFunction('test/resourceFunc', '<?php return static function($arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4 = 0) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();
        $args = ['foo', null, $this->createMock(ReflectionFunction::class)];
        self::assertEquals('Yes!', $f(...$args)->status);
    }

    public function testWhen_CalledDeeper_Expect_FunctionAvailableAsFunctionUnderNS(): void
    {
        $f = '\\rikmeijer\\Bootstrap\\testWhen_CalledDeeper_Expect_FunctionAvailableAsFunctionUnderNS\\test\\test\\resourceFunc';

        $this->createFunction('test/test/resourceFunc', '<?php return static function($arg1, ?string $arg2, \ReflectionFunction $arg3, string $arg4 = "") {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '};');

        Bootstrap::generate($this->getConfigurationRoot());
        $this->activateBootstrap();
        $args = ['foo', null, $this->createMock(ReflectionFunction::class)];
        self::assertEquals('Yes!', $f(...$args)->status);
    }


    public function testWhen_ResourcesAreGenerated_Expect_ResourcesAvailableAsFunctions(): void
    {
        $f0 = '\\rikmeijer\\Bootstrap\\f\\resourceFunc';
        $f1 = '\\rikmeijer\\Bootstrap\\f\\test\\resourceFunc';
        $f2 = '\\rikmeijer\\Bootstrap\\f\\test\\test\\resourceFunc';

        $this->createConfig('config', ['BOOTSTRAP' => ['namespace' => 'rikmeijer\\Bootstrap\\f'], 'test/test/resourceFunc' => ['status' => 'Yesss!']]);
        $this->createFunction('resourceFunc', '<?php return ' . $f0 . '\\configure(function(array $configuration, $arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '}, []);');
        $this->createFunction('test/resourceFunc', '<?php return ' . $f1 . '\\configure(function(array $configuration, $arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) {' . PHP_EOL . '   return (object)["status" => "Yes!"];' . PHP_EOL . '}, []);');
        $this->createFunction('test/test/resourceFunc', '<?php return ' . $f2 . '\\configure(function(array $configuration, $arg1, ?string $arg2, \ReflectionFunction $arg3, int|float $arg4) {' . PHP_EOL . '   return (object)["status" => $configuration["status"]];' . PHP_EOL . '}, []);');

        Bootstrap::generate($this->getConfigurationRoot());

        self::assertFileExists($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap.php');

        $this->activateBootstrap();
        $args = ['foo', null, $this->createMock(ReflectionFunction::class), 3.14];

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
        $args = ['foo', null, $this->createMock(ReflectionFunction::class), 3.14];

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

    private function activateBootstrap(): void
    {
        include $this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'bootstrap.php';
    }

    private function getBootstrapFQFN(string $function): string
    {
        return '\\rikmeijer\\Bootstrap\\' . $function;
    }

    private function getFQFN(string $function): string
    {
        return $this->getBootstrapFQFN($this->getName() . '\\' . $function);
    }

    private function getConfigurationRoot(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->getName();
    }

    protected function setUp(): void
    {
        $this->mkdir(Path::join($this->getConfigurationRoot()));
        $this->mkdir(Path::join($this->getConfigurationRoot(), 'bootstrap'));
        $this->streams['config'] = fopen($this->getConfigurationRoot() . DIRECTORY_SEPARATOR . 'config.php', 'wb');
    }

    private array $createdDirectories = [];

    private function mkdir(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, recursive: true)) {
            trigger_error("Unable to create " . $this->getConfigurationRoot());
        } else {
            $this->createdDirectories[] = $path;
        }
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
