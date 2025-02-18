<?php

namespace App\Entity\Patrimony;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Source\SourceMovie;
use App\Entity\Trait\Timestampable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

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

    const SCOPE_LIST = 'movie:list';
    const SCOPE_SUBLIST = 'movie:sublist';

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

    #[ORM\Column(type: Types::STRING, unique: true, nullable: true)]
    private string $cncId;

    #[ORM\Column(type: Types::STRING, unique: true, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private string $arteId;

    #[ORM\Column(type: Types::STRING, unique: true, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private string $canalVodId;

    #[ORM\Column(type: Types::STRING, unique: true, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private string $orangeVodId;

    #[ORM\Column(type: Types::STRING, unique: true, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private string $allocineId;

    #[ORM\Column(type: Types::STRING, unique: true, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private string $laCinetekId;

    #[ORM\Column(type: Types::STRING, unique: true, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private string $franceTvId;

    #[ORM\Column(type: Types::STRING, unique: true, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private string $isanId;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $visa;

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

    #[ORM\ManyToMany(targetEntity: Nationality::class, inversedBy: 'movies', cascade: ['detach'])]
    #[ORM\JoinTable(name: 'patrimony.movie_nationality')]
    private Collection $nationalities;

    #[ORM\ManyToMany(targetEntity: Genre::class, inversedBy: 'movies', cascade: ['detach'])]
    #[ORM\JoinTable(name: 'patrimony.movie_genre')]
    #[Groups([self::SCOPE_LIST])]
    private Collection $genres;

    #[ORM\ManyToMany(targetEntity: Director::class, inversedBy: 'movies', cascade: ['detach'])]
    #[ORM\JoinTable(name: 'patrimony.movie_director')]
    #[Groups([self::SCOPE_LIST])]
    private Collection $directors;

    #[ORM\ManyToOne(targetEntity: PublicRestriction::class, inversedBy: 'movies', cascade: ['detach'])]
    #[Groups([self::SCOPE_LIST])]
    private PublicRestriction $public;

    #[ORM\OneToMany(targetEntity: ActorMovie::class, mappedBy: 'movie', cascade: ['detach'])]
    #[Groups([self::SCOPE_LIST])]
    private Collection $actorMovies;

    #[ORM\OneToMany(targetEntity: Solution::class, mappedBy: 'movie')]
    #[Groups([self::SCOPE_LIST])]
    private Collection $solutions;

    #[ORM\OneToMany(targetEntity: SourceMovie::class, mappedBy: 'movie')]
    private Collection $sourceMovies;

    public function __construct() {
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

  public function getCncId(): string
  {
    return $this->cncId;
  }

  public function setCncId(string $cncId): void
  {
    $this->cncId = $cncId;
  }

  public function getArteId(): string
  {
    return $this->arteId;
  }

  public function setArteId(string $arteId): void
  {
    $this->arteId = $arteId;
  }

  public function getCanalVodId(): string
  {
    return $this->canalVodId;
  }

  public function setCanalVodId(string $canalVodId): void
  {
    $this->canalVodId = $canalVodId;
  }

  public function getOrangeVodId(): string
  {
    return $this->orangeVodId;
  }

  public function setOrangeVodId(string $orangeVodId): void
  {
    $this->orangeVodId = $orangeVodId;
  }

  public function getAllocineId(): string
  {
    return $this->allocineId;
  }

  public function setAllocineId(string $allocineId): void
  {
    $this->allocineId = $allocineId;
  }

  public function getLaCinetekId(): string
  {
    return $this->laCinetekId;
  }

  public function setLaCinetekId(string $laCinetekId): void
  {
    $this->laCinetekId = $laCinetekId;
  }

  public function getFranceTvId(): string
  {
    return $this->franceTvId;
  }

  public function setFranceTvId(string $franceTvId): void
  {
    $this->franceTvId = $franceTvId;
  }

  public function getIsanId(): string
  {
    return $this->isanId;
  }

  public function setIsanId(string $isanId): void
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

  public function getNationalities(): Collection
  {
    return $this->nationalities;
  }

  public function setNationalities(Collection $nationalities): void
  {
    $this->nationalities = $nationalities;
  }

  public function getGenres(): Collection
  {
    return $this->genres;
  }

  public function setGenres(Collection $genres): void
  {
    $this->genres = $genres;
  }

  public function getPublic(): PublicRestriction
  {
    return $this->public;
  }

  public function setPublic(PublicRestriction $public): void
  {
    $this->public = $public;
  }

  public function getActorMovies(): Collection
  {
    return $this->actorMovies;
  }

  public function setActorMovies(Collection $actorMovies): void
  {
    $this->actorMovies = $actorMovies;
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

  public function getDirectors(): Collection
  {
    return $this->directors;
  }

  public function setDirectors(Collection $directors): void
  {
    $this->directors = $directors;
  }
}