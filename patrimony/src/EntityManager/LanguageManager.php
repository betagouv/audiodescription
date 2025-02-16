<?php

namespace App\EntityManager;

use App\Entity\Patrimony\Language;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class responsible for managing genre-related operations.
 */
class LanguageManager
{

    public function __construct(
        private EntityManagerInterface $entityManager,
    )
    {

    }

    /**
     * Function to create language or update if it exists.
     *
     * @return Language
     *   Genre created or updated.
     */
    public function provide(string $languageName): ?Language
    {
        $repository = $this->entityManager->getRepository(Language::class);
        $language = $repository->findOneBy(['name' => $languageName]);

        if (is_null($language)) {
            $language = new Language();
            $language->setName($languageName);

            $this->entityManager->persist($language);
            $this->entityManager->flush();
        }

        return $language;
    }
}
