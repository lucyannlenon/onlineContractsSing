<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class AuthMainDto
{

    public \DateTime $birthday;

    public function __construct(
        #[Assert\NotBlank]
        public string $cpf,
        #[Assert\NotBlank]
        string        $birthday,
        #[Assert\NotBlank]
        public string $key,
    )
    {
        $time = strtotime($birthday);
        $this->birthday = (new \DateTime())->setTimestamp($time);
    }

    public function getCpf(): string
    {
        return preg_replace('/\D/', '', $this->cpf);
    }
}