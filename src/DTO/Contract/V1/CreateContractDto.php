<?php

namespace App\DTO\Contract\V1;

use Symfony\Component\Validator\Constraints as Assert;

class CreateContractDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string    $cpf,
        #[Assert\NotBlank]
        public \DateTimeInterface $birthday,
        #[Assert\NotBlank]
        public Payload   $payload

    )
    {

    }


}