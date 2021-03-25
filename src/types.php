<?php declare(strict_types=1);
namespace rikmeijer\Bootstrap\types{
function open(string $resourcePath) { return \rikmeijer\Bootstrap\resource\open(substr(__FILE__, 0, -4) . DIRECTORY_SEPARATOR . basename($resourcePath), false); }
}namespace rikmeijer\Bootstrap\types { 
    
        if (function_exists("rikmeijer\Bootstrap\types\arr") === false) {
    function _arr(): callable
{
	return \rikmeijer\Bootstrap\types\open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/types\\arr.php');
}

    function arr(?array $defaultValue = null): callable
{
	return (_arr())(...func_get_args());
}

    }
}namespace rikmeijer\Bootstrap\types { 
    
        if (function_exists("rikmeijer\Bootstrap\types\binary") === false) {
    function _binary(): callable
{
	return \rikmeijer\Bootstrap\types\open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/types\\binary.php');
}

    function binary(array $defaultCommand = null): callable
{
	return (_binary())(...func_get_args());
}

    }
}namespace rikmeijer\Bootstrap\types { 
    
        if (function_exists("rikmeijer\Bootstrap\types\boolean") === false) {
    function _boolean(): callable
{
	return \rikmeijer\Bootstrap\types\open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/types\\boolean.php');
}

    function boolean(?bool $defaultValue = null): callable
{
	return (_boolean())(...func_get_args());
}

    }
}namespace rikmeijer\Bootstrap\types { 
    
        if (function_exists("rikmeijer\Bootstrap\types\file") === false) {
    function _file(): callable
{
	return \rikmeijer\Bootstrap\types\open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/types\\file.php');
}

    function file(?string $defaultValue = null): callable
{
	return (_file())(...func_get_args());
}

    }
}namespace rikmeijer\Bootstrap\types { 
    
        if (function_exists("rikmeijer\Bootstrap\types\float") === false) {
    function _float(): callable
{
	return \rikmeijer\Bootstrap\types\open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/types\\float.php');
}

    function float(?float $defaultValue = null): callable
{
	return (_float())(...func_get_args());
}

    }
}namespace rikmeijer\Bootstrap\types { 
    
        if (function_exists("rikmeijer\Bootstrap\types\integer") === false) {
    function _integer(): callable
{
	return \rikmeijer\Bootstrap\types\open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/types\\integer.php');
}

    function integer(?int $defaultValue = null): callable
{
	return (_integer())(...func_get_args());
}

    }
}namespace rikmeijer\Bootstrap\types { 
    
        if (function_exists("rikmeijer\Bootstrap\types\mixed") === false) {
    function _mixed(): callable
{
	return \rikmeijer\Bootstrap\types\open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/types\\mixed.php');
}

    function mixed(mixed $defaultValue): callable
{
	return (_mixed())(...func_get_args());
}

    }
}namespace rikmeijer\Bootstrap\types { 
    
        if (function_exists("rikmeijer\Bootstrap\types\path") === false) {
    function _path(): callable
{
	return \rikmeijer\Bootstrap\types\open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/types\\path.php');
}

    function path(?string $defaultValue = null): callable
{
	return (_path())(...func_get_args());
}

    }
}namespace rikmeijer\Bootstrap\types { 
    
        if (function_exists("rikmeijer\Bootstrap\types\string") === false) {
    function _string(): callable
{
	return \rikmeijer\Bootstrap\types\open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/types\\string.php');
}

    function string(?string $defaultValue = null): callable
{
	return (_string())(...func_get_args());
}

    }
}