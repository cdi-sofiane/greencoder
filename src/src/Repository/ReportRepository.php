<?php

namespace App\Repository;

use App\Entity\Report;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Report|null find($id, $lockMode = null, $lockVersion = null)
 * @method Report|null findOneBy(array $criteria, array $orderBy = null)
 * @method Report[]    findAll()
 * @method Report[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Report::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Report $entity, bool $flush = true)
    {
        $this->_em->persist($entity);

        if ($flush) {
            $this->_em->flush();
            $entity->setLink($entity->getUuid() . '_' . $entity->getSlugName());
            $entity->setPdf($_ENV['PUBLIC_REPORT_STORAGE_LINK'] . $entity->getLink() . '.pdf');
            $entity->setCsv($_ENV['PUBLIC_REPORT_STORAGE_LINK'] . $entity->getLink() . '.csv');
            $this->_em->persist($entity);
            $this->_em->flush();
        }
        return $entity;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Report $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }


    public function findFilteredReports($filters, $account = null)
    {
        $query = $this->createQueryBuilder('report');
        $query->where('report.account = :account');
        $query->setParameter('account', $account);

        $query->andWhere('report.createdAt BETWEEN  :startAt AND  :endAt ');

        $query->setParameter('startAt',  (new \DateTimeImmutable($filters['startAt']))->add(new \DateInterval('P1D')));
        $query->setParameter('endAt',  (new \DateTimeImmutable($filters['endAt']))->add(new \DateInterval('P1D')));

        if (isset($filters['search']) !== null) {

            $query->andWhere('report.title like :title');
            $query->setParameter('title', '%' . $filters['search'] . '%');
        }
        if (isset($filters['isDeleted']) !== null) {
            $query->andWhere('report.isDeleted =:isDeleted');
            $query->setParameter('isDeleted',  $filters['isDeleted']);
        }
        if (isset($filters['order']) !== null) {
            if (isset($filters['sortBy']) !== null) {
                $query->orderBy('report.' . $filters['sortBy'], $filters['order']);
            } else {
                $query->orderBy('report.id', $filters['order']);
            }
        } else {
            $query->orderBy('report.id', 'ASC');
        }

        $q = $query->getQuery();
        return $q->getResult();
    }


    /*
    public function findOneBySomeField($value): ?Report
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
