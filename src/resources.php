<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

function resources(): array
{
    return [
        Configuration\path() . DIRECTORY_SEPARATOR . 'bootstrap' => ''
    ];
}