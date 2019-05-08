<?php

namespace App\Repository;

use App\Entity\DnsRecord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method DnsRecord|null find($id, $lockMode = null, $lockVersion = null)
 * @method DnsRecord|null findOneBy(array $criteria, array $orderBy = null)
 * @method DnsRecord[]    findAll()
 * @method DnsRecord[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DnsRecordRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, DnsRecord::class);
    }

    /**
     * @param int $id
     * @param int $status
     */
    public function updateStatus($id, $status = 0)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb = $qb
            ->update(' App\Entity\DnsRecord', 'u')
            ->set('u.status', ':val1')
            ->where('u.id = :val')
            ->setParameter('val', $id)
            ->setParameter('val1', $status)
            ->getQuery()->execute();
        return $qb;
    }

    /**
     * @param int $id
     * @param string $value
     * @param string $type
     */
    public function updateRecord($id, $value, $type, $date)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb = $qb
            ->update(' App\Entity\DnsRecord', 'u');
        if ($type == 'name') {
            $qb = $qb->set('u.name', ':val1');
        } else if ($type == 'value') {
            $qb = $qb->set('u.value', ':val1');
        } else {
            $qb = $qb->set('u.ttl', ':val1');
        }

        $qb = $qb
            ->set('u.dateModified', ':val2')
            ->where('u.id = :val')
            ->setParameter('val2', $date)
            ->setParameter('val', $id)
            ->setParameter('val1', $value)
            ->getQuery()->execute();
        return $qb;
    }

    /**
     * @param int $id
     */
    public function findCount($id)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb = $qb
            ->select('count(1) as count,u.dnsGroupId')
            ->from('App\Entity\DnsRecord', 'u')
            ->where('u.status = 1')
            ->groupBy('u.dnsGroupId')
            ->having('u.dnsGroupId in (:val1)')
            ->setParameter('val1', $id)
            ->getQuery()
            ->getArrayResult();
        return $qb;
    }

    /**
     * @param int $dnsGroupId
     * @param int $userId
     */
    public function successRecordCount($dnsGroupId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb = $qb
            ->select('f.result,COUNT(f.dnsGroupId) as count')
            ->from('App\Entity\DnsRecord', 'f')
            ->where('f.dnsGroupId = :id and f.status = 1')
            ->groupBy('f.result')
            ->setParameter('id', $dnsGroupId)
            ->getQuery()
            ->getArrayResult();
        return $qb;
    }

    /**
     *@param int $id
     */
    public function recordCount($id)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb = $qb
            ->select('f.dnsGroupId,f.result,COUNT(1) as count')
            ->from('App\Entity\DnsRecord', 'f')
            ->where('f.status = 1')
            ->groupBy('f.result,f.dnsGroupId')
            ->having('f.dnsGroupId in (:id)')
            ->setParameter('id', $id)
            ->getQuery()
            ->getArrayResult();
        return $qb;
    }
    /**
     * @param string $result
     * @param int $id
     */
    public function updateData($result, $id)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb = $qb
            ->update(' App\Entity\DnsRecord', 'u')
            ->set('u.result', ':val1')
            ->where('u.id = :val')
            ->setParameter('val', $id)
            ->setParameter('val1', $result)
            ->getQuery()->execute();
        return $qb;
    }
}
