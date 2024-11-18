<?php
namespace App\Repository;

use App\Entity\ExchangeRate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ExchangeRate|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExchangeRate|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExchangeRate[]    findAll()
 * @method ExchangeRate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExchangeRateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExchangeRate::class);
    }

    /**
     * Custom method to find the latest rate for a given base and target currency.
     */
    public function findLatestRate(string $baseCurrency, string $targetCurrency): ?ExchangeRate
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.baseCurrency = :base')
            ->andWhere('e.targetCurrency = :target')
            ->setParameter('base', $baseCurrency)
            ->setParameter('target', $targetCurrency)
            ->orderBy('e.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}