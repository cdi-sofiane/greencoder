<?php

namespace App\Repository;

use App\Entity\Role;
use App\Entity\UserAccountRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserAccountRole>
 *
 * @method UserAccountRole|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserAccountRole|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserAccountRole[]    findAll()
 * @method UserAccountRole[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserAccountRoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAccountRole::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(UserAccountRole $entity, bool $flush = true)
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
    public function remove(UserAccountRole $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findAccountOwner($account)
    {
        return $this->createQueryBuilder('userAccountRole')

            ->leftJoin('userAccountRole.role', 'role')
            ->leftJoin('userAccountRole.account', 'account')
            ->leftJoin('userAccountRole.user', 'user')
            ->where('role.code like :role')
            ->setParameter('role', '%' . Role::ROLE_ADMIN . '%')
            ->andWhere('account = :account')
            ->setParameter('account', $account)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAccountByAnyUserEmail($email)
    {
        return $this->createQueryBuilder('userAccountRole')

            ->leftJoin('userAccountRole.user', 'user')
            ->where('user.email like :email')
            ->setParameter('email', '%' . $email . '%')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function searchAccountMembers($args = null, $account)
    {
        $query = $this->createQueryBuilder('userAccountRole');

        $query->andWhere('user.createdAt BETWEEN  :startAt AND  :endAt ');
        $query->setParameter('startAt', isset($args['startAt']) != null ? new \DateTimeImmutable($args['startAt']) : new \DateTimeImmutable('1970-01-01'));
        $query->setParameter('endAt', isset($args['endAt']) != null ? (new \DateTimeImmutable($args['endAt']))->add(new \DateInterval('P1D')) : new \DateTimeImmutable('now'));
        $query->leftJoin('userAccountRole.user', 'user');
        $query->leftJoin('userAccountRole.account', 'account');
        if (isset($args['search']) != null) {
            $query->andWhere('user.firstName like :firstName');
            $query->setParameter('firstName', '%' . $args['search'] . '%');

            $query->orWhere('account.company like :company');
            $query->setParameter('company', '%' . $args['search'] . '%');

            $query->orWhere('user.lastName like :lastName');
            $query->setParameter('lastName', '%' . $args['search'] . '%');

            $query->orWhere('user.email like :email');
            $query->setParameter('email', '%' . $args['search'] . '%');

            $query->orWhere('user.roles like :role');
            $query->setParameter('role', '%' . $args['search'] . '%');
        }

        if (isset($args['user_uuid']) != null) {
            $query->andWhere('user.uuid = :uuid');
            $query->setParameter('uuid', $args['user_uuid']);
        }

        if ($account != null) {
            $query->andWhere('account = :acc');
            $query->setParameter('acc', $account);
        }
        if (isset($args['isActive']) != null) {
            $query->andWhere('user.isActive = :isActive');
            $query->setParameter('isActive', $args['isActive']);
        }

        if (isset($args['isArchive']) != null) {
            $query->andWhere('user.isArchive = :isArchive');
            $query->setParameter('isArchive', $args['isArchive']);
        }

        if (isset($args['isDelete']) != null) {
            $query->andWhere('user.isDelete = :isDelete');
            $query->setParameter('isDelete', $args['isDelete']);
        }
        if (isset($args['isConditionAgreed']) != null) {
            $query->andWhere('user.isConditionAgreed = :isConditionAgreed');
            $query->setParameter('isConditionAgreed', $args['isConditionAgreed']);
        }

        if (!empty($args['order'])) {
            if (!empty($args['sortBy'])) {

                $query->orderBy('user.' . $args['sortBy'], strtoupper($args['order']));
            } else {
                $query->orderBy('user.id', strtoupper($args['order']));
            }
        } else {
            $query->orderBy('user.id', 'ASC');
        }

        $query->getQuery();
        $q = $query->getQuery();
        return $q->getResult();
    }
}
