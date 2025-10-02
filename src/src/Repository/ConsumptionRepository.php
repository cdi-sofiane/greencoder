<?php

namespace App\Repository;

use App\Entity\Consumption;
use App\Entity\Encode;
use App\Entity\Video;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Consumption|null find($id, $lockMode = null, $lockVersion = null)
 * @method Consumption|null findOneBy(array $criteria, array $orderBy = null)
 * @method Consumption[]    findAll()
 * @method Consumption[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConsumptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Consumption::class);
    }

    public function create($consumption, $launched)
    {
        /**@var Consumption $consumption */
        $consumption
            ->setUuid('')
            ->setRate(1)
            ->setLaunched($launched)
            ->setCreatedAt(new \DateTimeImmutable('now'))
            ->setUpdatedAt(new \DateTimeImmutable('now'));

        $this->_em->persist($consumption);
        $this->_em->flush();
    }

    public function findConsumedVideos($arg = null)
    {
        $query = $this->createQueryBuilder('c');
        $query->andWhere('c.createdAt BETWEEN  :startAt AND  :endAt ');


        $query->setParameter('startAt', $arg['dateDebutFacturation'] != null ? new \DateTimeImmutable($arg['dateDebutFacturation']) : new \DateTimeImmutable('1970-01-01'));
        $query->setParameter('endAt', $arg['dateFin'] != null ? ( (new \DateTimeImmutable($arg['dateDebutFacturation']))->modify('+' . 30 . 'day')) : new \DateTimeImmutable('now'));

        if ($arg['video'] instanceof Video) {

            $query->andWhere('c.video = :original');
            $query->setParameter('original', $arg['video']);

        }
        if ($arg['video'] instanceof Encode) {
            $query->andWhere('c.encode = :encoded');
            $query->setParameter('encoded', $arg['video']);
        }
        if ($arg['launched'] != null) {
            $query->andWhere('c.launched = :launched');
            $query->setParameter('launched', $arg['launched']);
        }

        $q = $query->getQuery();
        return $q->getResult();
    }
    // /**
    //  * @return Consumption[] Returns an array of Consumption objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Consumption
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
