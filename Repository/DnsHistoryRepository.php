<?php

namespace App\Repository;

use App\Entity\DnsHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method DnsHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method DnsHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method DnsHistory[]    findAll()
 * @method DnsHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DnsHistoryRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, DnsHistory::class);
    }

    /**
     * @param Int $id
     */
    public function findRecordHistory($id)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query = $query
            ->select('u.id,u.name,u.type,u.value,u.dateTime,u.result,u.dnsRecordId')
            ->from(' App\Entity\DnsHistory', 'u')
            ->where('u.dnsRecordId = :val1')
            ->setParameter('val1', $id)
            ->getQuery()->getArrayResult();
            return $query;
    }
}
