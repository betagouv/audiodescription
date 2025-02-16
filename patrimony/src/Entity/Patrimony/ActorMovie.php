<?php

namespace App\Entity\Patrimony;

use ApiPlatform\Metadata\GetCollection;
use App\Entity\Trait\Timestampable;
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
#[ORM\Table(name: 'actormovie', schema: 'patrimony')]
#[ORM\HasLifecycleCallbacks]
class ActorMovie
{
    use Timestampable;

    const SCOPE_LIST = 'actormovie:list';
    const SCOPE_SUBLIST = 'actormovie:sublist';

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Movie::class, inversedBy: 'actorMovies')]
    private Movie $movie;

    #[ORM\ManyToOne(targetEntity: Actor::class, inversedBy: 'actorMovies')]
    #[Groups([self::SCOPE_LIST, self::SCOPE_SUBLIST])]
    private Actor $actor;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $role;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): void
    {
        $this->id = $id;
    }

    public function getMovie(): Movie
    {
        return $this->movie;
    }

    public function setMovie(Movie $movie): void
    {
        $this->movie = $movie;
    }

    public function getActor(): Actor
    {
        return $this->actor;
    }

    public function setActor(Actor $actor): void
    {
        $this->actor = $actor;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
    }
}