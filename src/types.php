<?php declare(strict_types=1);
namespace rikmeijer\Bootstrap\types;
use \rikmeijer\Bootstrap\Resource;
function open(string $functionIdentifier) { return Resource::open(substr(__FILE__, 0, -4) . DIRECTORY_SEPARATOR . $functionIdentifier, false); }
if (function_exists("rikmeijer\Bootstrap\types\arr") === false) {
function arr(?array $defaultValue = null): callable
{
	return open('arr.php')(...func_get_args());
}

}if (function_exists("rikmeijer\Bootstrap\types\binary") === false) {
function binary(array $defaultCommand = null): callable
{
	return open('binary.php')(...func_get_args());
}

}if (function_exists("rikmeijer\Bootstrap\types\boolean") === false) {
function boolean(?bool $defaultValue = null): callable
{
	return open('boolean.php')(...func_get_args());
}

}if (function_exists("rikmeijer\Bootstrap\types\file") === false) {
function file(?string $defaultValue = null): callable
{
	return open('file.php')(...func_get_args());
}

}if (function_exists("rikmeijer\Bootstrap\types\float") === false) {
function float(?float $defaultValue = null): callable
{
	return open('float.php')(...func_get_args());
}

}if (function_exists("rikmeijer\Bootstrap\types\integer") === false) {
function integer(?int $defaultValue = null): callable
{
	return open('integer.php')(...func_get_args());
}

}if (function_exists("rikmeijer\Bootstrap\types\mixed") === false) {
function mixed(mixed $defaultValue): callable
{
	return open('mixed.php')(...func_get_args());
}

}if (function_exists("rikmeijer\Bootstrap\types\path") === false) {
function path(?string $defaultValue = null): callable
{
	return open('path.php')(...func_get_args());
}

}if (function_exists("rikmeijer\Bootstrap\types\string") === false) {
function string(?string $defaultValue = null): callable
{
	return open('string.php')(...func_get_args());
}

}