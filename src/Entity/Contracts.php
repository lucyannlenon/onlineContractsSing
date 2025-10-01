<?php

namespace App\Entity;

use App\Enum\ContractTypeEnum;
use App\Repository\ContractsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContractsRepository::class)]
class Contracts
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $cpf = null;

    #[ORM\Column(length: 10)]
    private ?string $accessKey = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ContractTypeEnum|null $contractType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $template = null;


    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $birthday = null;

    #[ORM\Column]
    private array $payload = [];

    /**
     * @var Collection<int, ContractSignature>
     */
    #[ORM\OneToMany(targetEntity: ContractSignature::class, mappedBy: 'contract', cascade: ["persist"], orphanRemoval: true)]
    private Collection $signatures;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?bool $finish = false;

    #[ORM\Column]
    private ?bool $notified = false;

    public function getContractType(): ?ContractTypeEnum
    {
        return $this->contractType;
    }

    public function setContractType(?ContractTypeEnum $contractType): void
    {
        $this->contractType = $contractType;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setTemplate(?string $template): void
    {
        $this->template = $template;
    }

    public function __construct()
    {
        $this->signatures = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCpf(): ?string
    {
        return $this->cpf;
    }

    public function setCpf(string $cpf): static
    {
        $this->cpf = $cpf;

        return $this;
    }

    public function getAccessKey(): ?string
    {
        return $this->accessKey;
    }

    public function setAccessKey(string $accessKey): static
    {
        $this->accessKey = $accessKey;

        return $this;
    }

    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(\DateTimeInterface $birthday): static
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): static
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * @return Collection<int, ContractSignature>
     */
    public function getSignatures(): Collection
    {
        return $this->signatures;
    }

    public function addSignature(ContractSignature $signature): static
    {
        if (!$this->signatures->contains($signature)) {
            $this->signatures->add($signature);
            $signature->setContract($this);
        }

        return $this;
    }

    public function removeSignature(ContractSignature $signature): static
    {
        if ($this->signatures->removeElement($signature)) {
            // set the owning side to null (unless already changed)
            if ($signature->getContract() === $this) {
                $signature->setContract(null);
            }
        }

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

    public function getSignaturesByName(string $name): ?ContractSignature
    {
        foreach ($this->getSignatures() as $signature) {
            if ($signature->getName() == $name) {
                return $signature;
            }
        }
        return null;
    }

    public function isFinish(): ?bool
    {
        return $this->finish;
    }

    public function setFinish(bool $finish): static
    {
        $this->finish = $finish;

        return $this;
    }

    public function isNotified(): ?bool
    {
        return $this->notified;
    }

    public function setNotified(bool $notified): static
    {
        $this->notified = $notified;

        return $this;
    }
}
