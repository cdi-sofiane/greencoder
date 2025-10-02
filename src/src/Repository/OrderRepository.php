<?php

namespace App\Repository;

use App\Entity\Forfait;
use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }


    public function create($obj)
    {
        /**@var Order $obj */
        $this->_em->persist($obj);
        $this->_em->flush();
        return $obj;
    }

    public function update($obj)
    {
        /**@var Order $obj */
        $obj->setUpdatedAt(new \DateTimeImmutable('now'));
        $this->_em->persist($obj);
        $this->_em->flush();
        return $obj;
    }

    /**
     *
     * find active orders based on current date moment to encode and expiration date
     * @param $value
     * @return int|mixed|string
     */
    public function findActiveOrder($filters)
    {
        $query = $this->createQueryBuilder('o');

        $query->leftJoin('o.forfait', 'forfait');
        if (isset($filters['isActive'])) {
            $query->where('forfait.isActive = :isActive');
            $query->setParameter('isActive', $filters['isActive']);
        }

        if (isset($filters['uuid']) != null) {
            $query->andwhere('o.uuid = :uuid');
            $query->setParameter('uuid', $filters['uuid']);
        }
        if (isset($filters['nature']) != null) {
            $query->andwhere('forfait.nature = :nature');
            $query->setParameter('nature', $filters['nature']);
        }
        if (isset($filters['type']) != null) {
            $query->andwhere('forfait.type = :type');
            $query->setParameter('type', $filters['type']);
        }
        if (isset($filters['afterExpiredAt'])) {
            $query->andWhere('o.expireAt <= :afterExpiredAt ');
            $query->setParameter('afterExpiredAt', $filters['afterExpiredAt'] != null ? $filters['afterExpiredAt'] : new \DateTimeImmutable('now'));
        }
        if (isset($filters['beforExpiredAt'])) {
            $query->andWhere('o.expireAt >= :beforExpiredAt ');
            $query->setParameter('beforExpiredAt', $filters['beforExpiredAt'] != null ? $filters['beforExpiredAt'] : new \DateTimeImmutable('now'));
        }
        if (isset($filters['account']) != null) {
            $query->andWhere('o.account = :account');
            $query->setParameter('account', $filters['account']);
        }
        if (isset($filters['isConsumed'])) {
            $query->andWhere('o.isConsumed = :consumed');
            $query->setParameter('consumed', $filters['isConsumed']);
        }

        $query->orderBy('o.expireAt', 'ASC');
        $query->getQuery();
        $q = $query->getQuery();
        return $q->getResult();
    }

    public function findOrderToSold($filters)
    {
        $query = $this->createQueryBuilder('o');

        if (isset($filters['nature']) != null) {
            $query->leftJoin('o.forfait', 'forfait');
            $query->where('forfait.nature  != :nature');
            $query->setParameter('nature', $filters['nature']);
        }
        $query->andWhere('o.nextUpdate >= :nextUpdate ');
        $query->setParameter('nextUpdate', new \DateTimeImmutable('now'));

        if (isset($filters['account']) != null) {
            $query->andWhere('o.account = :account');
            $query->setParameter('account', $filters['account']);
        }
        if (isset($filters['isConsumed'])) {
            $query->andWhere('o.isConsumed = :consumed');
            $query->setParameter('consumed', $filters['isConsumed']);
        }

        $query->orderBy('o.nextUpdate', 'ASC');
        $query->getQuery();
        $q = $query->getQuery();
        return $q->getResult();
    }

    public function findOrderFreeExpiredPack($filters = null)
    {
        $query = $this->createQueryBuilder('o');
        $query->leftJoin('o.forfait', 'forfait');
        $query->andWhere('o.expireAt BETWEEN  :endAt AND  :startAt ');
        $query->setParameter('startAt', $filters['startAt']);
        $query->setParameter('endAt', $filters['endAt']);

        $query->orderBy('o.createdAt', 'ASC');
        $query->getQuery();
        $q = $query->getQuery();
        return $q->getResult();
    }


    public function totalCreditAvailable($filters)
    {
        $query = $this->createQueryBuilder('o');
        $query->select('SUM(o.seconds) as totalEncode , SUM(o.bits) as totalStorage');
        $query->leftJoin('o.forfait', 'forfait');
        if (isset($filters['isActive']) != null) {
            $query->where('forfait.isActive = :isActive');
            $query->setParameter('isActive', $filters['isActive']);
        }
        if (isset($filters['nature']) != null) {
            $query->where('forfait.nature != :nature');
            $query->setParameter('nature', $filters['nature']);
        }
        $query->andWhere('o.expireAt >= :expiredAt ');
        $query->setParameter('expiredAt', isset($filters['expiredAt']) != null ? $filters['expiredAt'] : new \DateTimeImmutable('now'));

        if (isset($filters['account']) != null) {
            $query->andWhere('o.account = :account');
            $query->setParameter('account', $filters['account']);
        }

        if (isset($filters['isConsumed']) != null) {
            $query->andWhere('o.isConsumed = :consumed');
            $query->setParameter('consumed', $filters['isConsumed']);
        }
        $query->getQuery();
        $q = $query->getQuery();
        return $q->getSingleResult();
    }

    public function findFilteredOrder($account = null, $filters)
    {
        $query = $this->createQueryBuilder('o');
        $query->leftJoin('o.forfait', 'forfait');
        if (isset($filters['isActive']) != null) {
            $query->where('forfait.isActive = :isActive');
            $query->setParameter('isActive', $filters['isActive']);
        }
        if (isset($filters['package_uuid']) != null) {
            $query->andWhere('forfait.uuid = :filterForfait');
            $query->setParameter('filterForfait', $filters['package_uuid']);
        }
        if (isset($filters['nature']) != null) {
            $query->where('forfait.nature = :nature');
            $query->setParameter('nature', $filters['nature']);
        }
        if (isset($filters['type']) != null) {
            $query->andwhere('forfait.type = :type');
            $query->setParameter('type', $filters['type']);
        }
        if ($account != null) {
            $query->andWhere('o.account = :account');
            $query->setParameter('account', $account);
        }
        if (isset($filters['isConsumed']) != null) {
            $query->andWhere('o.isConsumed = :consumed');
            $query->setParameter('consumed', $filters['isConsumed']);
        }

        $query->orderBy('o.expireAt', 'ASC');
        $query->getQuery();
        $q = $query->getQuery();
        return $q->getResult();
    }

    public function countOrderPerDay()
    {
        return $this->createQueryBuilder('o')
            ->select('count(o)')
            ->where('o.createdAt >= CURRENT_DATE()')
            ->andWhere('o.createdAt <= CURRENT_DATE()+1')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
