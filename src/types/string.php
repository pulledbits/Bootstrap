<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\types;

function string(?string $defaultValue = null): callable
{
    return mixed($defaultValue);
}