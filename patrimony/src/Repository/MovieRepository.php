<?php

namespace App\Repository;

use App\Entity\Patrimony\Movie;
use App\Entity\Patrimony\Partner;
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

    public function findByIds($ids, $code): array {
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

        if (isset($ids['tf1Id']) && !empty($ids['tf1Id'])) {
            $qb = $qb->orWhere('m.tf1Id = :tf1Id')
                ->setParameter('tf1Id', $ids['tf1Id']);
        }

        if (isset($ids['imdbId']) && !empty($ids['imdbId'])) {
            $qb = $qb->orWhere('m.imdbId = :imdbId')
                ->setParameter('imdbId', $ids['imdbId']);
        }

        if (isset($ids['plurimediaId']) && !empty($ids['plurimediaId'])) {
            $qb = $qb->orWhere('m.plurimediaId = :plurimediaId')
                ->setParameter('plurimediaId', $ids['plurimediaId']);
        }

        if (isset($code)) {
            $qb = $qb->orWhere('m.code = :code')
                ->setParameter('code', $code);
        }

        $result = $qb->getQuery()->execute();

        return $result;

        /**if (empty($result)) {
            return NULL;
        }

        if (count($result) == 1 ) {
            return $result[0];
        }

        dump(count($result));
        dump($result);

        throw new ImportException('Find more than one result');**/
    }

    public function updatedAllMovieWithPartner(Partner $partner) {
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

  public function findNewFreeMovies($platformCode = null): array
  {
    $qb = $this->createQueryBuilder('m');

    $qb->select('m') // HIDDEN pour pouvoir trier sans le retourner directement
      ->addSelect('s')
      ->addSelect('o')
      ->addSelect('p')
      ->leftJoin('m.solutions', 's')
      ->leftJoin('s.offer', 'o')
      ->leftJoin('s.partner', 'p')
      ->where('o.code = :freeAccess')
      ->andWhere('s.endRights >= :minEndDate')
      ->andWhere('s.startRights <= :now')
      ->andWhere($qb->expr()->andX(
        's.startRights IS NOT NULL',
        's.startRights >= :recentDate'
      ))
      ->andWhere('s.link IS NOT NULL')
      ->andWhere('m.hasAd = true')
      ->setParameter('freeAccess', 'FREE_ACCESS')
      ->setParameter('now', new \DateTime())
      ->setParameter('minEndDate', new \DateTime('+7 days'))
      ->setParameter('recentDate', new \DateTime('-7 days'));

    if (!is_null($platformCode)) {
      $qb->andWhere('p.code = :platform')
        ->setParameter('platform', $platformCode);
    }

    return $qb->getQuery()->getResult();
  }

  public function findNearEndFreeMovies(array $alreadySelected, int $maxResult): array
  {
    $qb = $this->createQueryBuilder('m');

    $qb->select('m') // HIDDEN pour pouvoir trier sans le retourner directement
    ->addSelect('s')
      ->addSelect('o')
      ->addSelect('p')
      ->leftJoin('m.solutions', 's')
      ->leftJoin('s.offer', 'o')
      ->leftJoin('s.partner', 'p')
      ->where('o.code = :freeAccess')
      ->andWhere('s.endRights IS NOT NULL')
      ->andWhere('s.endRights <= :nearEndDate')
      ->andWhere('s.endRights >= :nearNowDate')
      ->andWhere('s.link IS NOT NULL')
      ->andWhere('m.hasAd = true')
      
      ->orderBy('s.endRights', 'ASC')
      ->setParameter('freeAccess', 'FREE_ACCESS')
      ->setParameter('nearEndDate', new \DateTime('+15 days'))
      ->setParameter('nearNowDate', new \DateTime('+3 days'))
      ->setMaxResults($maxResult)
    ;
    
    if(!empty($alreadySelected)) {
      $qb->andWhere($qb->expr()->notIn('m', ':excluded'))
        ->setParameter('excluded', $alreadySelected);
    }
    
    return $qb->getQuery()->getResult();
  }

  public function findNotSelectedFreeMovies(array $alreadySelected): array
  {
    $qb = $this->createQueryBuilder('m');

    $qb->select('m') // HIDDEN pour pouvoir trier sans le retourner directement
    ->addSelect('s')
      ->addSelect('o')
      ->addSelect('p')
      ->leftJoin('m.solutions', 's')
      ->leftJoin('s.offer', 'o')
      ->leftJoin('s.partner', 'p')
      ->where('o.code = :freeAccess')
      /**->andWhere($qb->expr()->orX(
        's.endRights >= :now',
        's.endRights IS NULL'
      ))
      ->andWhere($qb->expr()->orX(
        's.startRights <= :now',
        's.startRights IS NULL'
      ))**/
      ->andWhere('s.endRights >= :now')
      ->andWhere('s.startRights <= :now')
      ->andWhere('s.link IS NOT NULL')
      ->andWhere($qb->expr()->notIn('m', ':excluded'))
      ->setParameter('excluded', $alreadySelected)
      ->setParameter('freeAccess', 'FREE_ACCESS')
      ->setParameter('now', new \DateTime());

    return $qb->getQuery()->getResult();
  }

  public function countFreeMovies(): int {
    $qb = $this->createQueryBuilder('m');

    $qb->select('COUNT(DISTINCT m.id)') // HIDDEN pour pouvoir trier sans le retourner directement
      ->leftJoin('m.solutions', 's')
      ->leftJoin('s.offer', 'o')
      ->leftJoin('s.partner', 'p')
      ->where('o.code = :freeAccess')
      /**->andWhere($qb->expr()->orX(
        's.endRights >= :now',
        's.endRights IS NULL'
      ))
      ->andWhere($qb->expr()->orX(
        's.startRights <= :now',
        's.startRights IS NULL'
      ))**/
      ->andWhere('s.endRights >= :now')
      ->andWhere('s.startRights <= :now')
      ->andWhere('m.hasAd = true')
      ->setParameter('freeAccess', 'FREE_ACCESS')
      ->setParameter('now', new \DateTime());

    $count = $qb->getQuery()->getSingleScalarResult();

    return $count;
  }
}