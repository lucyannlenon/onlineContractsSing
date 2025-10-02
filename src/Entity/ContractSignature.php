<?php

namespace App\Entity;

use App\Repository\ContractSignatureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: ContractSignatureRepository::class)]
class ContractSignature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Ignore]
    #[ORM\ManyToOne(inversedBy: 'signatures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Contracts $contract = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $signature = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $link = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContract(): ?Contracts
    {
        return $this->contract;
    }

    public function setContract(?Contracts $contract): static
    {
        $this->contract = $contract;

        return $this;
    }

    public function getSignature(): ?string
    {
        return $this->signature;
    }

    public function setSignature(string $signature): static
    {
        $this->signature = $signature;

        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): static
    {
        $this->link = $link;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
    public function md5Name(): ?string
    {
        return md5($this->name);
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'contract' => $this->contract?->getId(),
            'signature' => $this->signature,
            'link' => $this->link,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'name' => $this->name
        ];
    }
}
