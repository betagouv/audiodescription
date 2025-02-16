<?php

namespace App\Repository;

use App\Entity\Patrimony\Partner;
use App\Entity\Source\SourceMovie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SourceMovie>
 *
 * @method SourceMovie|null find($id, $lockMode = null, $lockVersion = null)
 * @method SourceMovie|null findOneBy(array $criteria, array $orderBy = null)
 * @method SourceMovie[]    findAll()
 * @method SourceMovie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SourceMovieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SourceMovie::class);
    }

    public function deleteAllByPartner(Partner $partner) {
        $qb = $this->createQueryBuilder('s')
            ->delete()
            ->where('s.partner = :partner')
            ->setParameter('partner', $partner);

        $qb->getQuery()->execute();
    }
}