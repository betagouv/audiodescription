<?php

namespace App\Entity\Patrimony;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Trait\Timestampable;
use App\Provider\MainGenresProvider;
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
    paginationEnabled: false,
    normalizationContext: ['groups' => [
        self::SCOPE_LIST,
    ]],
)]
#[GetCollection(
    uriTemplate: '/genres/main',
    routePrefix: '/api/v1',
    paginationEnabled: false,
    normalizationContext: ['groups' => [
        self::SCOPE_LIST,
    ]],
    provider: MainGenresProvider::class,
)]
#[ApiFilter(DateFilter::class)]
#[ORM\Entity]
#[ORM\Table(name: 'genre', schema: 'patrimony')]
#[ORM\HasLifecycleCallbacks]
class Genre
{
    use Timestampable;

    const SCOPE_LIST = 'genre:list';
    const SCOPE_SUBLIST = 'genre:sublist';

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

    #[ORM\ManyToMany(targetEntity: Movie::class, mappedBy: 'genres')]
    private Collection $movies;

    #[ORM\ManyToOne(targetEntity: Genre::class, inversedBy: 'secondaryGenres')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private ?Genre $mainGenre = null;

    #[ORM\OneToMany(targetEntity: Genre::class, mappedBy: 'mainGenre')]
    private Collection $secondaryGenres;

    public function __construct() {
        $this->movies = new ArrayCollection();
        $this->secondaryGenres = new ArrayCollection();
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

    public function getMovies(): Collection
    {
        return $this->movies;
    }

    public function setMovies(Collection $movies): void
    {
        $this->movies = $movies;
    }

    public function getMainGenre(): ?Genre
    {
        return $this->mainGenre;
    }

    public function setMainGenre(?Genre $mainGenre): void
    {
        $this->mainGenre = $mainGenre;
    }

    public function getSecondaryGenres(): Collection
    {
        return $this->secondaryGenres;
    }

    public function setSecondaryGenres(Collection $secondaryGenres): void
    {
        $this->secondaryGenres = $secondaryGenres;
    }

    #[Groups([self::SCOPE_LIST])]
    public function getUpdatedAt() {
        return $this->updatedAt;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s (%s)',
            $this->name,
            $this->code
        );
    }
}