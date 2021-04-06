<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\types;

function arr(?array $defaultValue = null): callable
{
    return mixed($defaultValue);
}