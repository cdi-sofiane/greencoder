<?php

namespace App\Repository;

use App\Entity\Simulation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Simulation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Simulation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Simulation[]    findAll()
 * @method Simulation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SimulationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Simulation::class);
    }

    public function create($simulation)
    {
        $this->_em->persist($simulation);
        $this->_em->flush();
        $simulation->setLink($simulation->getUuid() . '_' . $simulation->getSlugName() . '.' . $simulation->getExtension());
        $this->updateSimulation($simulation);
        return $simulation;
    }

    /**
     *
     * @param string $intervale use date interval to change constrain defaul P2D
     * @return int|mixed|string
     * @throws \Exception
     */
    public function findExpiredStorageVideo($filter = null, $intervale)
    {
        return $this->createQueryBuilder('simulation')
            ->Where('simulation.createdAt <= :createdAt')
            ->andWhere('simulation.isDeleted = :delete')
            ->setParameter('createdAt', (new \DateTimeImmutable('now'))->modify('-' . $intervale . 'day'))
            ->setParameter('delete', false)
            ->getQuery()
            ->getResult();
    }

    public function deleteSimulation($simulation)
    {
        /**@var Simulation $simulation */
        $simulation->setIsDeleted(true);
        $this->updateSimulation($simulation);
        return $simulation;
    }

    public function updateSimulation($simulation)
    {
        $this->_em->persist($simulation);
        $this->_em->flush();
        return $simulation;
    }
    // /**
    //  * @return Simulation[] Returns an array of Simulation objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Simulation
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
