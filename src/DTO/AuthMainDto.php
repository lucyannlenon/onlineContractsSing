<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class AuthMainDto
{

    #[Assert\NotBlank]
    public \DateTime $birthday;

    public function __construct(
        #[Assert\NotBlank]
        public string $cpf,
        string        $birthday,
        #[Assert\NotBlank]
        public string $key,
    )
    {
        $time = strtotime($birthday);
        $this->birthday = (new \DateTime())->setTimestamp($time);
    }
}