<?php

namespace App\Entity\Source;

use App\Entity\Patrimony\Movie;
use App\Entity\Patrimony\Partner;
use App\Entity\Patrimony\Solution;
use App\Entity\Trait\Timestampable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'source_movie', schema: 'patrimony')]
#[ORM\HasLifecycleCallbacks]
class SourceMovie
{
    use Timestampable;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    private string $title;

    #[ORM\Column(type: Types::STRING)]
    private string $code;

    #[ORM\Column(type: Types::STRING)]
    private string $internalPartnerId;

    #[ORM\ManyToOne(targetEntity: Partner::class, inversedBy: 'sourceMovies')]
    private Partner $partner;

    #[ORM\ManyToOne(targetEntity: Movie::class, inversedBy: 'sourceMovies')]
    private Movie $movie;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $hasAd;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $poster = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $synopsis = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $duration = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $productionYear = null;

    #[ORM\Column(type: Types::JSON)]
    private array $nationalities = [];

    #[ORM\Column(type: Types::JSON)]
    private array $genres = [];

    #[ORM\Column(type: Types::JSON)]
    private array $directors = [];

    #[ORM\Column(type: Types::JSON)]
    private array $casting = [];

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $public = null;

    #[ORM\Column(type: Types::JSON)]
    private ?array $externalIds = [];

    #[ORM\OneToMany(targetEntity: Solution::class, mappedBy: 'sourceMovie')]
    private Collection $solutions;

    public function __construct() {
        $this->solutions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getInternalPartnerId(): string
    {
        return $this->internalPartnerId;
    }

    public function setInternalPartnerId(string $internalPartnerId): void
    {
        $this->internalPartnerId = $internalPartnerId;
    }

    public function getPartner(): Partner
    {
        return $this->partner;
    }

    public function setPartner(Partner $partner): void
    {
        $this->partner = $partner;
    }

    public function getMovie(): Movie
    {
        return $this->movie;
    }

    public function setMovie(Movie $movie): void
    {
        $this->movie = $movie;
    }

    public function isHasAd(): bool
    {
        return $this->hasAd;
    }

    public function setHasAd(bool $hasAd): void
    {
        $this->hasAd = $hasAd;
    }

    public function getPoster(): ?string
    {
        return $this->poster;
    }

    public function setPoster(?string $poster): void
    {
        $this->poster = $poster;
    }

    public function getSynopsis(): ?string
    {
        return $this->synopsis;
    }

    public function setSynopsis(?string $synopsis): void
    {
        $this->synopsis = $synopsis;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): void
    {
        $this->duration = $duration;
    }

    public function getProductionYear(): ?int
    {
        return $this->productionYear;
    }

    public function setProductionYear(?int $productionYear): void
    {
        $this->productionYear = $productionYear;
    }

    public function getNationalities(): array
    {
        return $this->nationalities;
    }

    public function setNationalities(array $nationalities): void
    {
        $this->nationalities = $nationalities;
    }

    public function getGenres(): array
    {
        return $this->genres;
    }

    public function setGenres(array $genres): void
    {
        $this->genres = $genres;
    }

    public function getDirectors(): array
    {
        return $this->directors;
    }

    public function setDirectors(array $directors): void
    {
        $this->directors = $directors;
    }

    public function getCasting(): array
    {
        return $this->casting;
    }

    public function setCasting(array $casting): void
    {
        $this->casting = $casting;
    }

    public function getPublic(): ?string
    {
        return $this->public;
    }

    public function setPublic(?string $public): void
    {
        $this->public = $public;
    }

    public function getExternalIds(): ?array
    {
        return $this->externalIds;
    }

    public function setExternalIds(?array $externalIds): void
    {
        $this->externalIds = $externalIds;
    }

    public function getSolutions(): Collection
    {
        return $this->solutions;
    }

    public function setSolutions(Collection $solutions): void
    {
        $this->solutions = $solutions;
    }

    public function __toString(): string
    {

        return sprintf(
            '%s',
            $this->title,
        );
    }
}