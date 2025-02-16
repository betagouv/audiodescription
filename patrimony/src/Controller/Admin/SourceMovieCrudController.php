<?php

namespace App\Controller\Admin;

use App\Entity\Source\SourceMovie;
use App\Enum\PartnerCode;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class SourceMovieCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SourceMovie::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Source Movie')
            ->setEntityLabelInPlural('Source Movies');
    }

    public function configureFields(string $pageName): iterable
    {
        $baseFields = parent::configureFields($pageName);
        $baseFields[] = AssociationField::new('movie')
            ->setCrudController(MovieCrudController::class)
            ->autocomplete();

        return $baseFields;
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::updateEntity($entityManager, $entityInstance);

        $movie = $entityInstance->getMovie();
        $partner = $entityInstance->getPartner();

        // Synchronize partner code.
        switch ($partner->getCode()) {
            case PartnerCode::ARTE->value:
                $movie->setArteId($entityInstance->getInternalPartnerId());
                $movie->setTitle($entityInstance->getTitle());

                if ($movie->getProductionYear() == '0') {
                    $movie->setProductionYear($entityInstance->getProductionYear());
                }

                foreach($entityInstance->getSolutions() as $solution) {
                    $solution->setMovie($movie);
                    $entityManager->persist($solution);
                }
        }

        // Synchronize ad status.
        if ($entityInstance->isHasAd()) {
            $movie->setHasAd(TRUE);
        }

        $entityManager->persist($movie);
        $entityManager->flush();
    }
}
