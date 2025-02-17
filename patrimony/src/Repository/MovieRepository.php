<?php

namespace App\Repository;

use App\Entity\Patrimony\Movie;
use App\Importer\ImportException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Movie>
 *
 * @method Movie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Movie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Movie[]    findAll()
 * @method Movie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Movie::class);
    }

    public function findByIds($ids, $code): Movie|null {
        $qb = $this->createQueryBuilder('m')
            ->select();

        if (isset($ids['allocineId']) && !empty($ids['allocineId'])) {
            $qb = $qb->orWhere('m.allocineId = :allocineId')
                ->setParameter('allocineId', $ids['allocineId']);
        }

        if (isset($ids['canalVodId']) && !empty($ids['canalVodId'])) {
            $qb = $qb->orWhere('m.canalVodId = :canalVodId')
                ->setParameter('canalVodId', $ids['canalVodId']);
        }

        if (isset($ids['orangeVodId']) && !empty($ids['orangeVodId'])) {
            $qb = $qb->orWhere('m.orangeVodId = :orangeVodId')
                ->setParameter('orangeVodId', $ids['orangeVodId']);
        }

        if (isset($ids['isanId']) && !empty($ids['isanId'])) {
            $qb = $qb->orWhere('m.isanId = :isanId')
                ->setParameter('isanId', $ids['isanId']);
        }

        if (isset($ids['laCinetekId']) && !empty($ids['laCinetekId'])) {
          $qb = $qb->orWhere('m.laCinetekId = :laCinetekId')
            ->setParameter('laCinetekId', $ids['laCinetekId']);
        }

        if (isset($ids['arteId']) && !empty($ids['arteId'])) {
          $qb = $qb->orWhere('m.arteId = :arteId')
            ->setParameter('arteId', $ids['arteId']);
        }

        if (isset($ids['franceTvId']) && !empty($ids['franceTvId'])) {
          $qb = $qb->orWhere('m.franceTvId = :franceTvId')
            ->setParameter('franceTvId', $ids['franceTvId']);
        }

        if (isset($code)) {
            $qb = $qb->orWhere('m.code = :code')
                ->setParameter('code', $code);
        }

        $result = $qb->getQuery()->execute();

        if (empty($result)) {
            return NULL;
        }

        if (count($result) == 1 ) {
            return $result[0];
        }

        throw new ImportException('Find more than one result');
    }

    public function updatedAllMovieWithPartner($partner) {
        $qb = $this->createQueryBuilder('a');

        $qb->update(Movie::class, 'm')
            ->set('m.updatedAt', ':now')
            ->where(
                $qb->expr()->in(
                    'm.id',
                    $this->createQueryBuilder('b')
                        ->select('DISTINCT mov.id')
                        ->from(Movie::class, 'mov')
                        ->join('mov.solutions', 's')
                        ->where('s.partner = :partner')
                        ->getDQL()
                )
            )
            ->setParameter('now', new \DateTime(), Types::DATETIME_MUTABLE)
            ->setParameter('partner', $partner)
            ->getQuery()
            ->execute();
    }
}