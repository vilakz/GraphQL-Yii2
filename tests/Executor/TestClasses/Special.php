<?php

declare(strict_types=1);

namespace YiiGraphQL\Tests\Executor\TestClasses;

class Special
{
    /** @var string */
    public $value;

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
}
