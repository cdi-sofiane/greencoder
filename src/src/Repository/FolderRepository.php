<?php

namespace App\Repository;

use App\Entity\Folder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Folder>
 *
 * @method Folder|null find($id, $lockMode = null, $lockVersion = null)
 * @method Folder|null findOneBy(array $criteria, array $orderBy = null)
 * @method Folder[]    findAll()
 * @method Folder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FolderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Folder::class);
    }


    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function update(Folder $entity)
    {
        $entity->setUpdatedAt(new \DateTimeImmutable('now'));
        $this->_em->flush();
        return $entity;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Folder $entity, bool $flush = true)
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
    public function remove(Folder $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }


    public function findAccountFolders($filters = [])
    {

        $query = $this->createQueryBuilder('f');

        $query->select('f');
        $query->leftJoin('f.account', 'account')
            ->where('account =:acc')
            ->setParameter('acc', $filters['account']);
        if (isset($filters['folder']) != null) {
            $query->andWhere('f =:folder')
                ->setParameter('folder', $filters['folder']);
        }
        if (isset($filters['level'])) {
            $query->andWhere('f.level =:lvl')
                ->setParameter('lvl', $filters['level']);
        }
        if (isset($filters['isInTrash'])) {
            $query->andWhere('f.isInTrash =:isInTrash')
                ->setParameter('isInTrash', $filters['isInTrash']);
        }
        return $query->getQuery()->getResult();
    }
    // /**
    //  * @return Folder[] Returns an array of Folder objects
    //  */
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
    public function findOneBySomeField($value): ?Folder
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */


    public function getFoldersInTrashSince30Days()
    {
        $date = date('Y-m-d h:i:s', strtotime("-30 days"));

        return $this->createQueryBuilder('f')
            ->select('f')
            ->where('f.isInTrash =:isTrashed')
            ->setParameter('n30days', $date)
            ->where('f.updatedAt < :n30days')
            ->setParameter('n30days', $date)
            ->getQuery()
            ->getResult();
    }

    public function findFilteredFolders($filters, $account = null)
    {
        $query = $this->createQueryBuilder('folder');
        $query->where('folder.account = :account');
        $query->setParameter('account', $filters['account']);
        $query->andWhere('folder.isInTrash = true');
        $query->andWhere('folder.level = 0');

        if (isset($filters['search']) !== null) {
            $query->andWhere('folder.name like :name')
                ->setParameter('name', '%' . $filters['search'] . '%');
        }

        if (isset($filters['order']) !== null) {
            if (isset($filters['sortBy']) !== null) {
                $query->orderBy('folder.' . $filters['sortBy'], $filters['order']);
            } else {
                $query->orderBy('folder.id', $filters['order']);
            }
        } else {
            $query->orderBy('folder.id', 'ASC');
        }

        $q = $query->getQuery();
        return $q->getResult();
    }

    public function findFoldersByUuids($uuids, $account)
    {
        $query = $this->createQueryBuilder('f');

        return $query->where($query->expr()->in('f.uuid', ':uuids'))
            ->andWhere('f.account = :account')
            ->setParameters([
                'uuids' => $uuids,
                'account' => $account,
            ])
            ->getQuery()
            ->getResult();
    }
}
