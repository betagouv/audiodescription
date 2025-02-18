<?php

namespace App\Entity\Patrimony;

use ApiPlatform\Metadata\GetCollection;
use App\Entity\Trait\Timestampable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[GetCollection(
    routePrefix: '/api/v1',
    normalizationContext: ['groups' => [
        self::SCOPE_LIST,
    ]],
)]
#[ORM\Entity]
#[ORM\Table(name: 'offer', schema: 'patrimony')]
#[ORM\HasLifecycleCallbacks]
class Offer
{
    use Timestampable;

    const SCOPE_LIST = 'offer:list';
    const SCOPE_SUBLIST = 'offer:sublist';

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(type: Types::STRING)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private string $name;

    #[ORM\Column(type: Types::STRING, unique: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private string $code;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private ?int $displayOrder;

    #[ORM\OneToMany(targetEntity: Solution::class, mappedBy: 'offer')]
    private Collection $solutions;

    public function __construct() {
        $this->solutions = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getDisplayOrder(): ?int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(?int $displayOrder): void
    {
        $this->displayOrder = $displayOrder;
    }

    public function getSolutions(): Collection
    {
        return $this->solutions;
    }

    public function setSolutions(Collection $solutions): void
    {
        $this->solutions = $solutions;
    }
}