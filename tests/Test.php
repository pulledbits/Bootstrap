<?php declare(strict_types=1);


namespace rikmeijer\Bootstrap\tests;


class Test
{
    static function __set_state(array $properties): self
    {
        return new self;
    }

}