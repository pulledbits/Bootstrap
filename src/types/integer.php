<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\types;

function integer(?int $defaultValue = null): callable
{
    return mixed($defaultValue);
}