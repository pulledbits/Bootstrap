<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\types;

function float(?float $defaultValue = null): callable
{
    return mixed($defaultValue);
}