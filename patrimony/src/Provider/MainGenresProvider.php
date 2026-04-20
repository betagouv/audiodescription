<?php

namespace App\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Patrimony\Genre;
use App\Repository\GenreRepository;

/** @implements ProviderInterface<Genre> */
class MainGenresProvider implements ProviderInterface
{
    public function __construct(
        private GenreRepository $genreRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     * @return array<Genre>
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->genreRepository->findMainGenres();
    }
}
