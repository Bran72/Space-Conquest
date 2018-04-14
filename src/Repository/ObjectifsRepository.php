<?php

namespace App\Repository;

use App\Entity\Objectifs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Objectifs|null find($id, $lockMode = null, $lockVersion = null)
 * @method Objectifs|null findOneBy(array $criteria, array $orderBy = null)
 * @method Objectifs[]    findAll()
 * @method Objectifs[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ObjectifsRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Objectifs::class);
    }

    /*
    public function findBySomething($value)
    {
        return $this->createQueryBuilder('o')
            ->where('o.something = :value')->setParameter('value', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */
}
