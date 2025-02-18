<?php

namespace App\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\GenreRepository;

class MainGenresProvider implements ProviderInterface
{
    public function __construct(
        private GenreRepository $genreRepository,
    )
    {

    }

    /**
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return array<Genre>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->genreRepository->findMainGenres();
    }
}