<?php

namespace App\Repository;

use App\Entity\Patrimony\Partner;
use App\Entity\Patrimony\Solution;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Solution>
 *
 * @method Solution|null find($id, $lockMode = null, $lockVersion = null)
 * @method Solution|null findOneBy(array $criteria, array $orderBy = null)
 * @method Solution[]    findAll()
 * @method Solution[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SolutionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Solution::class);
    }

    public function deleteAllByPartner(Partner $partner) {
        $qb = $this->createQueryBuilder('s')
            ->delete()
            ->where('s.partner = :partner')
            ->setParameter('partner', $partner);

        $qb->getQuery()->execute();
    }
}