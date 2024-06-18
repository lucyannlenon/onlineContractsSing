<?php

namespace App\DTO\Contract\V1;

use Symfony\Component\Validator\Constraints as Assert;

class Products
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,
        #[Assert\GreaterThan(value: 0)]
        public string $value)
    {
    }
}