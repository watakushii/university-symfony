<?php

namespace App\Repository;

use App\Entity\AuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditLog>
 */
class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    /**
     * Find audit logs for a specific entity.
     *
     * @param string $entityType
     * @param int $entityId
     * @return AuditLog[]
     */
    public function findByEntity(string $entityType, int $entityId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.entityType = :entityType')
            ->andWhere('a.entityId = :entityId')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find audit logs for a specific user.
     *
     * @param string $userId
     * @return AuditLog[]
     */
    public function findByUser(string $userId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find audit logs by action.
     *
     * @param string $action
     * @return AuditLog[]
     */
    public function findByAction(string $action): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.action = :action')
            ->setParameter('action', $action)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

