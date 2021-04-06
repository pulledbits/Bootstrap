<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\types;

function boolean(?bool $defaultValue = null): callable
{
    return mixed($defaultValue);
}