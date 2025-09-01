<?php

namespace App\Entity\Patrimony;

use ApiPlatform\Metadata\GetCollection;
use App\Entity\Source\SourceMovie;
use App\Entity\Trait\Timestampable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[GetCollection(
  routePrefix: '/api/v1',
    normalizationContext: ['groups' => [
        self::SCOPE_LIST
    ]],
)]
#[ORM\Entity]
#[ORM\Table(name: 'solution', schema: 'patrimony')]
#[ORM\HasLifecycleCallbacks]
class Solution
{
    use Timestampable;

    const SCOPE_LIST = 'solution:list';
    const SCOPE_SUBLIST = 'solution:sublist';

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private Uuid $id;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private string $internalPartnerId;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private ?string $condition;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private string $link;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private ?DateTimeInterface $startRights = NULL;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private ?DateTimeInterface $endRights = NULL;

    #[ORM\ManyToOne(targetEntity: SourceMovie::class, inversedBy: 'solutions')]
    private SourceMovie $sourceMovie;

    #[ORM\ManyToOne(targetEntity: Partner::class, inversedBy: 'solutions')]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private Partner $partner;

    #[ORM\ManyToOne(targetEntity: Offer::class, inversedBy: 'solutions')]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private Offer $offer;

    #[ORM\ManyToOne(targetEntity: Movie::class, inversedBy: 'solutions')]
    private  $movie;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): void
    {
        $this->id = $id;
    }

    public function getInternalPartnerId(): string
    {
        return $this->internalPartnerId;
    }

    public function setInternalPartnerId(string $internalPartnerId): void
    {
        $this->internalPartnerId = $internalPartnerId;
    }

    public function getCondition(): ?string
    {
        return $this->condition;
    }

    public function setCondition(?string $condition): void
    {
        $this->condition = $condition;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setLink(string $link): void
    {
        $this->link = $link;
    }

    public function getStartRights(): ?DateTimeInterface
    {
        return $this->startRights;
    }

    public function setStartRights(DateTimeInterface $startRights): void
    {
        $this->startRights = $startRights;
    }

    public function getEndRights(): ?DateTimeInterface
    {
        return $this->endRights;
    }

    public function setEndRights(DateTimeInterface $endRights): void
    {
        $this->endRights = $endRights;
    }

    public function getSourceMovie(): SourceMovie
    {
        return $this->sourceMovie;
    }

    public function setSourceMovie(SourceMovie $sourceMovie): void
    {
        $this->sourceMovie = $sourceMovie;
    }

    public function getPartner(): Partner
    {
        return $this->partner;
    }

    public function setPartner(Partner $partner): void
    {
        $this->partner = $partner;
    }

    public function getOffer(): Offer
    {
        return $this->offer;
    }

    public function setOffer(Offer $offer): void
    {
        $this->offer = $offer;
    }

    /**
     * @return mixed
     */
    public function getMovie()
    {
        return $this->movie;
    }

    /**
     * @param mixed $movie
     */
    public function setMovie($movie): void
    {
        $this->movie = $movie;
    }
}