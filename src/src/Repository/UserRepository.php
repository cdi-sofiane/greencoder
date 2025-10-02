<?php

namespace App\Repository;

use App\Entity\Tags;
use App\Entity\User;
use App\Entity\UserAccountRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public $passwordEncoder;

    public function __construct(ManagerRegistry $registry, UserPasswordEncoderInterface $passwordEncoder)
    {
        parent::__construct($registry, User::class);
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function register($user)
    {

        $user->setPassword($this->passwordEncoder->encodePassword($user, $user->getPassword()));

        $this->_em->persist($user);
        $this->_em->flush();
        return $user;
    }

    public function updatePassword($user, $flush = true)
    {
        $user->setPassword($this->passwordEncoder->encodePassword($user, $user->getPassword()));

        $this->_em->persist($user);
        if ($flush) {
            $this->_em->flush();
        }
        return $user;
    }

    public function activeAccount($user)
    {
        /**@var  Entity\User $user */
        $user->setIsActive(true);
        $this->_em->persist($user);
        $this->_em->flush();
        return $user;
    }

    public function update($user)
    {

        /**@var User $user */
        $user->setUpdatedAt(new \DateTimeImmutable('now'));
        $this->_em->persist($user);
        $this->_em->flush();
        return $user;
    }

    public function findUsersWithFilters($args = null, $account = null)
    {

        $query = $this->createQueryBuilder('user');
        $query->andWhere('user.createdAt BETWEEN  :startAt AND  :endAt ');
        $query->setParameter('startAt', isset($args['startAt']) != null ? new \DateTimeImmutable($args['startAt']) : new \DateTimeImmutable('1970-01-01'));
        $query->setParameter('endAt', isset($args['endAt']) != null ? (new \DateTimeImmutable($args['endAt']))->add(new \DateInterval('P1D')) : new \DateTimeImmutable('now'));
        $query->leftJoin('user.userAccountRole', 'userAccountRole');
        if (isset($args['search']) != null) {
            $query->andWhere('user.firstName like :firstName');
            $query->setParameter('firstName', '%' . $args['search'] . '%');

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
            $query->andWhere('userAccountRole.account = :acc');
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
        $args['order'] = "ASC";
        $args['sortBy'] = "email";
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

    public function findUserWithoutActiveOrder($filters)
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles like :role')
            ->setParameter('role', '%' . $filters['roles'] . '%')
            ->leftJoin('u.orders', 'orders')
            ->andWhere('orders.expireAt < :startAt')
            ->setParameter('startAt', $filters['startAt'])
            ->groupBy('orders.user')
            ->getQuery()
            ->getResult();
    }
    public function findAccountWithoutActiveOrder($filters)
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.account', 'account')
            ->where('u.roles like :role')
            ->setParameter('role', '%' . $filters['roles'] . '%')
            ->leftJoin('account.orders', 'orders')
            ->andWhere('orders.expireAt < :startAt')
            ->setParameter('startAt', $filters['startAt'])
            ->groupBy('orders.account')
            ->getQuery()
            ->getResult();
    }
    public function findUsersWithApiKey()
    {

        return $this->createQueryBuilder('u')
            ->where("u.apiKey is not null and u.apiKey != '' ")
            ->getQuery()
            ->getResult();
    }

    public function addTagsToUser(User $user, $tags = [])
    {
        $listUserTags = $this->findTagsName($user);

        $addtags = [];

        foreach ($tags as $tag) {
            foreach ($listUserTags[0] as $userTags) {
                if (!in_array($userTags, $tags)) {
                    $tag = (new Tags())
                        ->setUuid('')
                        ->setUser($user)
                        ->setTagName($tag);
                    $this->_em->persist($tag);
                }
            }
        }
        $this->_em->flush();
    }


    public function findTagsName($user)
    {
        return $this->createQueryBuilder('u')
            ->select('tags.tagName')
            ->where('u.uuid = :uuid')
            ->setParameter('uuid', $user->getUuid())
            ->leftJoin('u.tags', 'tags')
            ->getQuery()
            ->getResult();
    }
    public function findAccountPilote($account)
    {
        return $this->createQueryBuilder('u')

            ->where('u.roles like :role')
            ->setParameter('role', '%' . 'ROLE_PILOTE' . '%')
            ->andWhere('u.account = :account')
            ->setParameter('account', $account)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAccountAdmin($account)
    {
        return $this->createQueryBuilder('u')

            ->where('u.roles like  :r')
            ->setParameter('r', '%' . 'ROLE_VIDMIZER' . '%')
            ->orWhere('u.roles like  :roles')
            ->setParameter('roles', '%' .  'ROLE_DEV' . '%')
            ->andWhere('u.account  :account')
            ->setParameter('account', $account)
            ->getQuery()
            ->getOneOrNullResult();
    }
    public function findAllAccountPilote()
    {
        return $this->createQueryBuilder('u')

            ->where('u.roles like :role')
            ->setParameter('role', '%' . 'ROLE_PILOTE' . '%')
            ->getQuery()
            ->getResult();
    }

    public function findOneByEmail($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->join('u.account', 'a')
            ->addSelect('a')
            ->andWhere('u.email = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findUserWithAccount($user, $account): ?User
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.userAccountRole', 'userAccountRole')
            ->leftJoin('userAccountRole.account', 'account')
            ->leftJoin('account.accountRoleRight', 'accountRoleRight')
            ->andWhere('u = :user')
            ->setParameter('user', $user)
            ->andWhere('account = :account')
            ->setParameter('account', $account)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findUserAccountRoleAndRights($account, $user)
    {


        return $this->createQueryBuilder('user')

            ->leftJoin('user.UserAccountRole', 'userAccountRole')
            ->leftJoin('userAccountRole.account', 'account')
            ->leftJoin('userAccountRole.role', 'role')
            ->leftJoin('account.AccountRoleRights', 'accountRoleRight')
            ->where('user = :user')
            ->setParameter('user', $user)
            ->andWhere('account = :account')
            ->setParameter('account', $account)
            ->andWhere('accountRoleRight.account = :accountRight')
            ->setParameter('accountRight', $account)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findUserAccountRoleByEmail($userEmail)
    {
        return $this->createQueryBuilder('user')
            ->leftJoin('user.UserAccountRole', 'userAccountRole')
            ->leftJoin('userAccountRole.account', 'account')
            ->leftJoin('userAccountRole.role', 'role')
            ->leftJoin('account.AccountRoleRights', 'accountRoleRight')

            ->getQuery()
            ->getOneOrNullResult();
    }
}
