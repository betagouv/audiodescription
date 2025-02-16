<?php

namespace App\Entity\Patrimony;

use ApiPlatform\Metadata\GetCollection;
use App\Entity\Source\SourceMovie;
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
#[ORM\Table(name: 'partner', schema: 'patrimony')]
#[ORM\HasLifecycleCallbacks]
class Partner
{
    use Timestampable;

    const SCOPE_LIST = 'partner:list';
    const SCOPE_SUBLIST = 'partner:sublist';

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private Uuid $id;

    #[ORM\Column(type: Types::STRING)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private string $name;

    #[ORM\Column(type: Types::STRING, unique: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private string $code;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private ?string $logo;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private ?string $condition;

    #[ORM\OneToMany(targetEntity: Solution::class, mappedBy: 'partner')]
    private Collection $solutions;

    #[ORM\OneToMany(targetEntity: SourceMovie::class, mappedBy: 'partner')]
    private Collection $sourceMovies;

    public function __construct() {
        $this->solutions = new ArrayCollection();
        $this->sourceMovies = new ArrayCollection();
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

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): void
    {
        $this->logo = $logo;
    }

    public function getCondition(): ?string
    {
        return $this->condition;
    }

    public function setCondition(?string $condition): void
    {
        $this->condition = $condition;
    }

    public function getSolutions(): Collection
    {
        return $this->solutions;
    }

    public function setSolutions(Collection $solutions): void
    {
        $this->solutions = $solutions;
    }

    public function getSourceMovies(): Collection
    {
        return $this->sourceMovies;
    }

    public function setSourceMovies(Collection $sourceMovies): void
    {
        $this->sourceMovies = $sourceMovies;
    }
}