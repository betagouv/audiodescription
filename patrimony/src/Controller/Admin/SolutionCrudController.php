<?php

namespace App\Controller\Admin;

use App\Entity\Patrimony\Movie;
use App\Entity\Patrimony\Solution;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class SolutionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Solution::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Solution')
            ->setEntityLabelInPlural('Solutions');
    }
}
