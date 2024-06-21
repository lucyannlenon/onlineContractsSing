<?php

namespace App\DTO\Contract\V1;
use Symfony\Component\Validator\Constraints as Assert;

class Payload
{
    /**
     * @param Products[] $products
     * @param BenefitsProducts[] $benefitsProducts
     */
    public function __construct(
        #[Assert\NotBlank]
        public string $city,
        #[Assert\NotBlank]
        public string $plan,
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,
        #[Assert\NotBlank]
        public string $rg_ie,
        #[Assert\GreaterThanOrEqual(value:0)]
        public string $discount_installation,
        #[Assert\NotBlank]
        public string $phones,
        #[Assert\NotBlank]
        public string $address,
        #[Assert\GreaterThan(value: 1)]
        #[Assert\LessThan(value: 28)]
        public string $due_day,
        #[Assert\NotBlank]
        public string $cpf_cnpj,
        #[Assert\NotBlank]
        #[Assert\GreaterThanOrEqual(value:0)]
        public string $discount,
        #[Assert\NotBlank]
        public array $products,
        #[Assert\NotBlank]
        public string $guarantee,
        #[Assert\NotBlank]
        public \DateTimeInterface $birth_date,
        #[Assert\NotBlank]
        public string $postal_code,
        #[Assert\NotBlank]
        public string $neighborhood,
        #[Assert\NotBlank]
        public string $contract_name,
        #[Assert\NotBlank]
        public string $contract_months,
        #[Assert\NotBlank]
        public string $contractor_name,
        #[Assert\NotBlank]
        public string $download_upload,
        #[Assert\NotBlank]
        public array $benefits_products,
        #[Assert\NotBlank]
        public string $installation_cost,
        #[Assert\NotBlank]
        public string $total_benefits_value
    ) {
    }
}