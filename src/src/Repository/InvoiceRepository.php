<?php

namespace App\Repository;

use App\Entity\Invoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Invoice|null find($id, $lockMode = null, $lockVersion = null)
 * @method Invoice|null findOneBy(array $criteria, array $orderBy = null)
 * @method Invoice[]    findAll()
 * @method Invoice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    public function findInvoices($account, $filters)
    {
        $query = $this->createQueryBuilder('f');
            $query->andWhere('f.account = :account')
            ->setParameter('account', $account);
            if (isset($filters['search']) !== null) {
                $query->andWhere('f.invoiceNumber like :invoiceNumber');
                $query->setParameter('invoiceNumber', '%' . $filters['search'] . '%');
            }
        return $query->orderBy('f.createdAt', 'ASC')
                     ->getQuery()
                     ->getResult();
    }
}
