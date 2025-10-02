<?php

namespace App\Repository;

use App\Entity\Account;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserAccountRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Video;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\Serializer\Serializer;

/**
 * @extends ServiceEntityRepository<Account>
 *
 * @method Account|null find($id, $lockMode = null, $lockVersion = null)
 * @method Account|null findOneBy(array $criteria, array $orderBy = null)
 * @method Account[]    findAll()
 * @method Account[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Account $entity, bool $flush = true)
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
        return $entity;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Account $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findFilteredAccount($filter = null, $user = null)
    {
        $query = $this->createQueryBuilder('account');

        if ($user != null) {
            $query->leftJoin('account.userAccountRole', 'userAccountRole');
            $query->andwhere('userAccountRole.user = :accountUser');
            $query->setParameter('accountUser', $user);
        }
        if (isset($filter['search']) != null) {
            $query->orwhere('account.name like :name');
            $query->setParameter('name', '%' . $filter['search'] . '%');

            $query->orwhere('account.company like :company');
            $query->setParameter('company', '%' . $filter['search'] . '%');

            $query->orwhere('account.email like :email');
            $query->setParameter('email', '%' . $filter['search'] . '%');
        }


        if (isset($filter['isMultiAccount']) != null) {
            $query->andwhere('account.isMultiAccount =:isMultiAccount');
            $query->setParameter('isMultiAccount', $filter['isMultiAccount']);
        }
        if (isset($filter['account_uuid']) != null) {
            $query->andwhere('account.uuid =:accountuuid');
            $query->setParameter('accountuuid', $filter['account_uuid']);
        }

        if (isset($filter['isActive']) != null) {
            $query->andwhere('account.isActive =:isActive');
            $query->setParameter('isActive', $filter['isActive']);
        }
        if (isset($filter['usages']) != null) {
            $query->andwhere('account.usages =:usage');
            $query->setParameter('usage', $filter['usages']);
        }

        if (isset($filter['order']) !== null) {
            if (isset($filter['sortBy']) !== null) {
                $query->orderBy('account.' . $filter['sortBy'], $filter['order']);
            } else {
                $query->orderBy('account.id', $filter['order']);
            }
        } else {
            $query->orderBy('account.id', 'ASC');
        }

        $q = $query->getQuery();
        return $q->getResult();;
    }

    public function findUserAccount($account_uuid, $user)
    {
        $query = $this->createQueryBuilder('account');
        $query->where('account.uuid = :uuid');
        $query->setParameter('uuid', $account_uuid);
        if ($user != null) {
            $query->andWhere('account = :account');
            $query->setParameter('account', $user->getAccount());
        }

        $q = $query->getQuery();
        return $q->getOneOrNullResult();
    }
    public function findPilote($account)
    {
        $query = $this->createQueryBuilder('account');
        // $query->join(User::class);
        $user = null;
        if ($user != null) {
            $query->where('account = :account');
            $query->setParameter('account', $user->getAccount());
        }

        $q = $query->getQuery();
        return $q->getResult();;
    }

    public function findPiloteFromAccount($account)
    {
        $query = $this->createQueryBuilder('account');
        // $query->join(User::class);
        $user = null;
        if ($user != null) {
            $query->where('account = :account');
            $query->setParameter('account', $user->getAccount());
        }

        $q = $query->getQuery();
        return $q->getResult();;
    }

    public function findAccountWithPilote($filters)
    {

        $query = $this->createQueryBuilder('account');

        $query->leftJoin('account.users', 'users');
        $query->andWhere('users.roles like :role');

        $query->setParameter('role',  '%' . $filters['roles'] . '%');


        $q = $query->getQuery();
        return $q->getResult();
    }
    public function findAccountWithoutActiveOrder($filters)
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.users', 'users')
            ->andwhere('users.roles like :role')
            ->setParameter('role', '%' . $filters['roles'] . '%')
            ->leftJoin('a.orders', 'orders')
            ->andWhere('orders.expireAt < :startAt')
            ->setParameter('startAt', $filters['startAt'])
            ->groupBy('orders.account')
            ->getQuery()
            ->getResult();
    }

    public function findAccount($filter = null, $account = null)
    {

        $query = $this->createQueryBuilder('account');
        if (isset($filter['uuid']) != null) {
            $query->where('account.uuid =:account_uuid');
            $query->setParameter('account_uuid',   $filter['uuid']);
        }

        $query->leftJoin('account.users', 'users');
        if ($account != null) {

            $query->andWhere('users.account =:account');
            $query->setParameter('account',  $account);
        }
        if (isset($filter['isMultiAccount']) != null) {
            $query->andWhere('account.isMultiAccount =:isMultiAccount');
            $query->setParameter('isMultiAccount',   $filter['isMultiAccount']);
        }

        if (isset($filter['isActive']) != null) {
            $query->andWhere('account.isActive  =:isActive');
            $query->setParameter('isActive',   $filter['isActive']);
        }
        $q = $query->getQuery();
        return $q->getOneOrNullResult();
    }


    public function lastCreatedAccountWithPilote($filters = null)
    {

        $queryBuilder =  $this->createQueryBuilder('a');
        return $queryBuilder
            ->leftJoin('a.userAccountRole', 'usr')
            ->leftJoin('usr.user', 'u')
            ->leftJoin('usr.role', 'a_role')
            // ->where('u.roles like :u_role')
            // ->setParameter('u_role', '%' . $filters['roles'] . '%')
            ->where('a_role.code =:a_role ')
            ->setParameter('a_role', Role::ROLE_ADMIN)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults(30)
            ->getQuery()
            ->getResult();
    }

    public function findAccountByVideo($video = null)
    {

        $queryBuilder =  $this->createQueryBuilder('a');
        return $queryBuilder
            ->leftJoin('a.userAccountRole', 'usr')
            ->leftJoin('usr.user', 'u')
            ->andWhere(':video MEMBER OF u.videos')
            ->setParameter('video', $video)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
