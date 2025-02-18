<?php

namespace App\Controller\Admin;

use App\Entity\Patrimony\PublicRestriction;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class PublicRestrictionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PublicRestriction::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Public')
            ->setEntityLabelInPlural('Publics');
    }
}
