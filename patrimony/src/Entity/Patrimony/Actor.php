<?php

namespace App\Entity\Patrimony;

use ApiPlatform\Metadata\ApiResource;
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
#[ORM\Table(name: 'actor', schema: 'patrimony')]
#[ORM\HasLifecycleCallbacks]
class Actor
{
    use Timestampable;

    const SCOPE_LIST = 'actor:list';
    const SCOPE_SUBLIST = 'actor:sublist';

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private Uuid $id;

    #[ORM\Column(type: Types::STRING)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private string $fullname;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private ?string $firstname;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private ?string $lastname;

    #[ORM\Column(type: Types::STRING, unique: true)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private string $code;

    #[ORM\OneToMany(targetEntity: ActorMovie::class, mappedBy: 'actor')]
    private Collection $actorMovies;

    public function __construct() {
        $this->actorMovies = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): void
    {
        $this->id = $id;
    }

    public function getFullname(): string
    {
        return $this->fullname;
    }

    public function setFullname(string $fullname): void
    {
        $this->fullname = $fullname;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): void
    {
        $this->firstname = $firstname;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): void
    {
        $this->lastname = $lastname;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getActorMovies(): Collection
    {
        return $this->actorMovies;
    }

    public function setActorMovies(Collection $actorMovies): void
    {
        $this->actorMovies = $actorMovies;
    }
}