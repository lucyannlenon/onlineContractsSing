<?php

namespace App\DTO\Contract\V2;

use App\DTO\Contract\V1\Payload;
use Symfony\Component\Validator\Constraints as Assert;

class CreateContractDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string             $cpf,
        #[Assert\NotBlank]
        public \DateTimeInterface $birthday,
        #[Assert\NotBlank]
        public string             $contractTemplate

    )
    {

    }


}