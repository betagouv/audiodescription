<?php

namespace App\Controller\Admin;

use App\Entity\Patrimony\Genre;
use App\Entity\Patrimony\Movie;
use App\Entity\Patrimony\Partner;
use App\Entity\Patrimony\Solution;
use App\Entity\Source\SourceMovie;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\SearchMode;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractDashboardController
{

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->redirectToRoute('admin_movie_index');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Audiodescription Patrimony');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section('Patrimoine');


        yield MenuItem::linkToCrud('Genres', 'fas fa-list', Genre::class);
        yield MenuItem::linkToCrud('Partenaires', 'fas fa-list', Partner::class);
        yield MenuItem::linkToCrud('Films', 'fas fa-list', Movie::class);
        yield MenuItem::linkToCrud('Solutions', 'fas fa-list', Solution::class);

        yield MenuItem::section('Source');
        yield MenuItem::linkToCrud('Source films', 'fas fa-list', SourceMovie::class);
    }

    public function configureCrud(): Crud
    {
        return Crud::new()
            ->setSearchMode(SearchMode::ALL_TERMS)
            ->setDefaultSort(['updatedAt' => 'DESC'])
            ->setPaginatorPageSize(50)
            ->setPaginatorRangeSize(3);
    }
}
