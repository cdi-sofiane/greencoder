<?php

namespace App\Repository;

use App\Entity\AccountRoleRight;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccountRoleRight>
 *
 * @method AccountRoleRight|null find($id, $lockMode = null, $lockVersion = null)
 * @method AccountRoleRight|null findOneBy(array $criteria, array $orderBy = null)
 * @method AccountRoleRight[]    findAll()
 * @method AccountRoleRight[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccountRoleRightRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccountRoleRight::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(AccountRoleRight $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(AccountRoleRight $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }


    public function findRightWithAccountRole($filter = null)
    {
        return $this->createQueryBuilder('arr')
            ->join('arr.rights', 'right')
            ->where('right.code = :code')
            ->setParameter('code', $filter['rightCode'])
            ->andWhere('arr.account = :account')
            ->setParameter('account', $filter['account'])
            ->andWhere('arr.role = :role')
            ->setParameter('role', $filter['role'])
            ->getQuery()
            ->getOneOrNullResult();
    }


    /*
    public function findOneBySomeField($value): ?AccountRoleRight
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
