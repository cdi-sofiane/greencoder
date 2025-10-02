<?php

namespace App\Repository;

use App\Entity\Account;
use App\Entity\Encode;
use App\Entity\User;
use App\Entity\Video;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Encode|null find($id, $lockMode = null, $lockVersion = null)
 * @method Encode|null findOneBy(array $criteria, array $orderBy = null)
 * @method Encode[]    findAll()
 * @method Encode[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EncodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Encode::class);
    }


    public function save($encode)
    {
        $this->_em->persist($encode);
        $this->_em->flush();
        return $encode;
    }

    public function deleteEncode(Encode $encode)
    {
        $encode->setIsDeleted(true);
        $encode->setDeletedAt(new \DateTimeImmutable('now'));
        $this->updateEncode($encode);
        return $encode;
    }

    public function updateEncode(Encode $encode, $flush = true)
    {
        $encode->setUpdatedAt(new \DateTimeImmutable('now'));
        $this->_em->persist($encode);

        if ($flush) {
            $this->_em->flush();
        }
        return $encode;
    }

    public function findEndecodedFileWithHighestSize(Video $video)
    {

        return $this->createQueryBuilder('e')
            ->andWhere('e.video = :videoId')
            ->setParameter('videoId', $video)
            ->orderBy('e.size', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
    }
    public function findAccountEncodeVideo($encode, Account $account)
    {

        return $this->createQueryBuilder('encode')
            ->where('encode.uuid = :encode')
            ->leftJoin('encode.video', 'video')
            ->leftJoin('video.account', 'account')
            ->andWhere('account = :account')
            ->setParameter('encode', $encode)
            ->setParameter('account', $account)
            ->getQuery()
            ->getOneOrNullResult();
    }
    /*
    public function findOneBySomeField($value): ?Encode
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
