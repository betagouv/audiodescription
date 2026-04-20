<?php

namespace App\Entity\Patrimony;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Source\SourceMovie;
use App\Entity\Trait\Timestampable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
#[GetCollection(
    routePrefix: '/api/v1',
    normalizationContext: ['groups' => [
        self::SCOPE_LIST,
        Genre::SCOPE_SUBLIST,
        Nationality::SCOPE_SUBLIST,
        PublicRestriction::SCOPE_SUBLIST,
        Director::SCOPE_SUBLIST,
        Partner::SCOPE_SUBLIST,
        Solution::SCOPE_SUBLIST,
        ActorMovie::SCOPE_SUBLIST,
        Actor::SCOPE_SUBLIST,
        Offer::SCOPE_SUBLIST,
    ]],
)]
#[ApiFilter(DateFilter::class)]
#[ORM\Entity]
#[ORM\Table(name: 'movie', schema: 'patrimony')]
#[ORM\HasLifecycleCallbacks]
class Movie
{
    use Timestampable;

    public const SCOPE_LIST = 'movie:list';
    public const SCOPE_SUBLIST = 'movie:sublist';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private string $title;

    #[ORM\Column(type: Types::STRING)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private string $code;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $cncId = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private ?string $arteId = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private ?string $canalVodId = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private ?string $orangeVodId = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private ?string $allocineId = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private ?string $laCinetekId = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private ?string $franceTvId = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private ?string $tf1Id = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private ?string $imdbId = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private ?string $plurimediaId = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private ?string $isanId = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $visa = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private bool $hasAd;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups([self::SCOPE_LIST])]
    private ?string $poster;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups([self::SCOPE_LIST])]
    private ?string $synopsis;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups([self::SCOPE_LIST])]
    private ?int $duration;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups([self::SCOPE_LIST])]
    private ?int $productionYear;

    /** @var Collection<int, Nationality> */
    #[ORM\ManyToMany(targetEntity: Nationality::class, inversedBy: 'movies', cascade: ['detach'])]
    #[ORM\JoinTable(name: 'patrimony.movie_nationality')]
    private Collection $nationalities;

    /** @var Collection<int, Genre> */
    #[ORM\ManyToMany(targetEntity: Genre::class, inversedBy: 'movies', cascade: ['detach'])]
    #[ORM\JoinTable(name: 'patrimony.movie_genre')]
    #[Groups([self::SCOPE_LIST])]
    private Collection $genres;

    /** @var Collection<int, Director> */
    #[ORM\ManyToMany(targetEntity: Director::class, inversedBy: 'movies', cascade: ['detach'])]
    #[ORM\JoinTable(name: 'patrimony.movie_director')]
    #[Groups([self::SCOPE_LIST])]
    private Collection $directors;

    #[ORM\ManyToOne(targetEntity: PublicRestriction::class, inversedBy: 'movies', cascade: ['detach'])]
    #[Groups([self::SCOPE_LIST])]
    private ?PublicRestriction $public = null;

    /** @var Collection<int, ActorMovie> */
    #[ORM\OneToMany(targetEntity: ActorMovie::class, mappedBy: 'movie', cascade: ['detach'])]
    #[Groups([self::SCOPE_LIST])]
    private Collection $actorMovies;

    /** @var Collection<int, Solution> */
    #[ORM\OneToMany(targetEntity: Solution::class, mappedBy: 'movie')]
    #[Groups([self::SCOPE_LIST])]
    private Collection $solutions;

    /** @var Collection<int, SourceMovie> */
    #[ORM\OneToMany(targetEntity: SourceMovie::class, mappedBy: 'movie')]
    private Collection $sourceMovies;

    public function __construct()
    {
        $this->nationalities = new ArrayCollection();
        $this->directors = new ArrayCollection();
        $this->genres = new ArrayCollection();
        $this->actorMovies = new ArrayCollection();
        $this->solutions = new ArrayCollection();
        $this->sourceMovies = new ArrayCollection();
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

    public function getCncId(): string|null
    {
        return $this->cncId;
    }

    public function setCncId(?string $cncId): void
    {
        $this->cncId = $cncId;
    }

    public function getArteId(): string|null
    {
        return $this->arteId;
    }

    public function setArteId(?string $arteId): void
    {
        $this->arteId = $arteId;
    }

    public function getCanalVodId(): string|null
    {
        return $this->canalVodId;
    }

    public function setCanalVodId(?string $canalVodId): void
    {
        $this->canalVodId = $canalVodId;
    }

    public function getOrangeVodId(): string|null
    {
        return $this->orangeVodId;
    }

    public function setOrangeVodId(?string $orangeVodId): void
    {
        $this->orangeVodId = $orangeVodId;
    }

    public function getAllocineId(): string|null
    {
        return $this->allocineId;
    }

    public function setAllocineId(?string $allocineId): void
    {
        $this->allocineId = $allocineId;
    }

    public function getLaCinetekId(): string|null
    {
        return $this->laCinetekId;
    }

    public function setLaCinetekId(?string $laCinetekId): void
    {
        $this->laCinetekId = $laCinetekId;
    }

    public function getFranceTvId(): string|null
    {
        return $this->franceTvId;
    }

    public function setFranceTvId(?string $franceTvId): void
    {
        $this->franceTvId = $franceTvId;
    }

    public function getIsanId(): string|null
    {
        return $this->isanId;
    }

    public function setIsanId(?string $isanId): void
    {
        $this->isanId = $isanId;
    }

    public function getVisa(): ?string
    {
        return $this->visa;
    }

    public function setVisa(?string $visa): void
    {
        $this->visa = $visa;
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

    /** @return Collection<int, Nationality> */
    public function getNationalities(): Collection
    {
        return $this->nationalities;
    }

    /** @param Collection<int, Nationality> $nationalities */
    public function setNationalities(Collection $nationalities): void
    {
        $this->nationalities = $nationalities;
    }

    /** @return Collection<int, Genre> */
    public function getGenres(): Collection
    {
        return $this->genres;
    }

    /** @param Collection<int, Genre> $genres */
    public function setGenres(Collection $genres): void
    {
        $this->genres = $genres;
    }

    public function getPublic(): ?PublicRestriction
    {
        return $this->public;
    }

    public function setPublic(?PublicRestriction $public): void
    {
        $this->public = $public;
    }

    /** @return Collection<int, ActorMovie> */
    public function getActorMovies(): Collection
    {
        return $this->actorMovies;
    }

    /** @param Collection<int, ActorMovie> $actorMovies */
    public function setActorMovies(Collection $actorMovies): void
    {
        $this->actorMovies = $actorMovies;
    }

    /** @return Collection<int, Solution> */
    public function getSolutions(): Collection
    {
        return $this->solutions;
    }

    /** @param Collection<int, Solution> $solutions */
    public function setSolutions(Collection $solutions): void
    {
        $this->solutions = $solutions;
    }

    /** @return Collection<int, SourceMovie> */
    public function getSourceMovies(): Collection
    {
        return $this->sourceMovies;
    }

    /** @param Collection<int, SourceMovie> $sourceMovies */
    public function setSourceMovies(Collection $sourceMovies): void
    {
        $this->sourceMovies = $sourceMovies;
    }

    public function __toString(): string
    {
        $directors = array_map(function (Director $director) {
            return $director->getFullname();
        }, $this->getDirectors()->toArray());

        $directorsString = count($directors) > 0 ? implode(', ', $directors) : '????';

        return sprintf(
            '%s (%s par %s)',
            $this->title,
            $this->productionYear ?? '????',
            $directorsString
        );
    }

    /** @return Collection<int, Director> */
    public function getDirectors(): Collection
    {
        return $this->directors;
    }

    /** @param Collection<int, Director> $directors */
    public function setDirectors(Collection $directors): void
    {
        $this->directors = $directors;
    }

    public function getTf1Id(): string|null
    {
        return $this->tf1Id;
    }

    public function setTf1Id(string $tf1Id): void
    {
        $this->tf1Id = $tf1Id;
    }

    public function getImdbId(): string|null
    {
        return $this->imdbId;
    }

    public function setImdbId(string $imdbId): void
    {
        $this->imdbId = $imdbId;
    }

    public function getPlurimediaId(): string|null
    {
        return $this->plurimediaId;
    }

    public function setPlurimediaId(string $plurimediaId): void
    {
        $this->plurimediaId = $plurimediaId;
    }
}
