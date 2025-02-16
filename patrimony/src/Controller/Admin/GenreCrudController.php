<?php

namespace App\Controller\Admin;

use App\Entity\Patrimony\Genre;
use App\Entity\Patrimony\Movie;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class GenreCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Genre::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $baseFields = parent::configureFields($pageName);
        $baseFields[] = AssociationField::new('mainGenre')
            ->setCrudController(GenreCrudController::class)
            ->autocomplete();

        return $baseFields;
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::updateEntity($entityManager, $entityInstance);

        $qb = $entityManager->createQueryBuilder();
        $qb->update(Movie::class, 'm')
            ->set('m.updatedAt', ':now')
            ->where(':genre MEMBER OF m.genres')
            ->setParameter('now', new \DateTime())
            ->setParameter('genre', $entityInstance)
            ->getQuery()
            ->execute();

        $entityManager->flush();
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Genre')
            ->setEntityLabelInPlural('Genres');
    }
}
