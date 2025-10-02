<?php

namespace App\Repository;

use App\Entity\Forfait;
use App\Services\AuthorizationService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Forfait|null find($id, $lockMode = null, $lockVersion = null)
 * @method Forfait|null findOneBy(array $criteria, array $orderBy = null)
 * @method Forfait[]    findAll()
 * @method Forfait[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ForfaitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Forfait::class);
    }

    public function save($object)
    {
        $this->_em->persist($object);
        $this->_em->flush();
        return $object;
    }

    /**
     * @return Forfait[] Returns an array of Forfait objects
     */

    public function findWithFilters($user = null, $filters = null, $role = null)
    {
        $query = $this->createQueryBuilder('forfait');
        $query->andWhere('forfait.createdAt BETWEEN  :startAt AND  :endAt ');


        $query->setParameter('startAt', isset($filters['startAt']) != null ? new \DateTimeImmutable($filters['startAt']) : new \DateTimeImmutable('1970-01-01'));
        $query->setParameter('endAt', isset($filters['endAt']) != null ? (new \DateTimeImmutable($filters['endAt']))->add(new \DateInterval('P1D')) : new \DateTimeImmutable('now'));

        if (isset($filters['search']) != null) {
            $query->andWhere('forfait.name like :name');
            $query->setParameter('name', '%' . $filters['search'] . '%');
        }

        if (isset($filters['isActive']) != null) {

            $query->andWhere('forfait.isActive = :active');
            $query->setParameter('active', $filters['isActive']);
        }
        if (isset($filters['package_uuid']) != null) {

            $query->andWhere('forfait.uuid = :package');
            $query->setParameter('package', $filters['package_uuid']);
        }
        if (isset($filters['isEntreprise']) != null) {

            $query->andWhere('forfait.isEntreprise = :entreprise');
            $query->setParameter('entreprise', $filters['isEntreprise']);
        }
        if (isset($filters['isAutomatic']) != null) {

            $query->andWhere('forfait.isAutomatic = :automatic');
            $query->setParameter('automatic', $filters['isAutomatic']);
        }
        if (isset($filters['isDelete']) != null) {

            $query->andWhere('forfait.isDelete = :delete');
            $query->setParameter('delete', $filters['isDelete']);
        }

        if ($user != null) {
            $query->andWhere('forfait.createdBy = :createdBy');
            $query->setParameter('createdBy', $user);
        }
        if (isset($filters['nature']) != null) {
            $query->andWhere('forfait.nature = :nature');
            $query->setParameter('nature', $filters['nature']);
        }
        if ($role == AuthorizationService::AS_USER) {
            if ($filters['type'] === null) {
                $filters['type'] = Forfait::TYPE_GRATUIT;
            }
            $query = $this->typeWithRole($query, $filters);
        } else {
            if (isset($filters['type']) != null) {
                $query->andWhere('forfait.type = :type');
                $query->setParameter('type', $filters['type']);
            }
        }

        if (isset($filters['sortBy']) != null) {
            $query->orderBy('forfait.' . $filters['sortBy'], isset($filters['order']) != null ? strtoupper($filters['order']) : 'ASC');
        }
        $query->getQuery();
        $q = $query->getQuery();
        return $q->getResult();
    }

    private function typeWithRole($query, $filters = null)
    {
        $query->setParameter('type', $filters['type']);
        switch ($filters['type']) {
            case 'Gratuit':
                return $query->andWhere('forfait.type != :type');
            default:
                return $query->andWhere('forfait.type = :type');
        }
    }

     /**
      * @return Forfait[] Returns an array of Forfait objects
      */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Forfait
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
