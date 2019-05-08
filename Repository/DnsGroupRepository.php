<?php

namespace App\Repository;

use App\Entity\DnsGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method DnsGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method DnsGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method DnsGroup[]    findAll()
 * @method DnsGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DnsGroupRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, DnsGroup::class);
    }

    /**
     * @param int $userid
     */
    public function findByStatus($userid)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb = $qb
            ->select('dnsgroup.id,dnsgroup.name,dnsgroup.datecreated,dnsgroup.userid,dnsgroup.privacy,dnsgroup.notification')
            ->from('App\Entity\DnsGroup', 'dnsgroup')
            ->where('dnsgroup.status = 1')
            ->andWhere('(dnsgroup.privacy = 1 and dnsgroup.userid = :val) or (dnsgroup.privacy = 0)')
            ->setParameter('val', $userid)
            ->getQuery();
        return $qb;
    }

    /**
     * @param int $id
     * @param int $status
     */
    public function updateGrpStatus($id, $status = 0)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb = $qb
            ->update(' App\Entity\DnsGroup', 'u')
            ->set('u.status', ':val1')
            ->where('u.id = :val')
            ->setParameter('val', $id)
            ->setParameter('val1', $status)
            ->getQuery()->execute();
        return $qb;
    }

    /**
     * @param array $data
     * @param object $obj
     * @return integer
     */
    public function insertData($data, $obj)
    {
        $classmethods = get_class_methods($obj);
        foreach ($data as $key => $value) {
            $field = 'set' . ucfirst($key);
            if (in_array($field, $classmethods)) {
                $obj->$field($value);
            }
        }
        $this->getEntityManager()->persist($obj);
        $this->getEntityManager()->flush();
        return $obj->getId();
    }

     /**
     * @param array $data  
     */
    public function updateemailprivacy($data){
        
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb = $qb
            ->update(' App\Entity\DnsGroup', 'u')
            ->set('u.privacy', ':val1')
            ->set('u.notification', ':val2')
            ->where('u.id = :val3')
            ->setParameter('val1', $data['privacy'])
            ->setParameter('val2', $data['notification'])
            ->setParameter('val3', $data['id'])
            ->getQuery()->execute();
        return $qb;
    }

}
