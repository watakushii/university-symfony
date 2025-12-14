<?php

namespace App\Service;

use App\Entity\AuditLog;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service for automatic audit logging of entity changes.
 */
class AuditService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $auditLogger,
        private readonly Security $security,
        private readonly RequestStack $requestStack
    ) {
    }

    /**
     * Log a CREATE operation.
     *
     * @param object $entity
     * @param array|null $newValues
     * @param string|null $description
     */
    public function logCreate(object $entity, ?array $newValues = null, ?string $description = null): void
    {
        $this->log('CREATE', $entity, null, $newValues, $description);
    }

    /**
     * Log an UPDATE operation.
     *
     * @param object $entity
     * @param array|null $oldValues
     * @param array|null $newValues
     * @param string|null $description
     */
    public function logUpdate(object $entity, ?array $oldValues = null, ?array $newValues = null, ?string $description = null): void
    {
        $this->log('UPDATE', $entity, $oldValues, $newValues, $description);
    }

    /**
     * Log a DELETE operation.
     *
     * @param object $entity
     * @param array|null $oldValues
     * @param string|null $description
     */
    public function logDelete(object $entity, ?array $oldValues = null, ?string $description = null): void
    {
        $this->log('DELETE', $entity, $oldValues, null, $description);
    }

    /**
     * Create audit log entry.
     *
     * @param string $action
     * @param object $entity
     * @param array|null $oldValues
     * @param array|null $newValues
     * @param string|null $description
     */
    private function log(string $action, object $entity, ?array $oldValues = null, ?array $newValues = null, ?string $description = null): void
    {
        try {
            $user = $this->security->getUser();
            $entityType = get_class($entity);
            $entityId = $this->getEntityId($entity);

            $auditLog = new AuditLog();
            $auditLog->setAction($action);
            $auditLog->setEntityType($entityType);
            $auditLog->setEntityId($entityId);
            $auditLog->setOldValues($oldValues);
            $auditLog->setNewValues($newValues);
            $auditLog->setDescription($description);

            if ($user) {
                $auditLog->setUserId((string) $user->getUserIdentifier());
                if (method_exists($user, 'getEmail')) {
                    $auditLog->setUserEmail($user->getEmail());
                }
            }

            $request = $this->requestStack->getCurrentRequest();
            if ($request) {
                $auditLog->setIpAddress($request->getClientIp());
            }

            $this->entityManager->persist($auditLog);
            $this->entityManager->flush();

            // Also log to Monolog audit channel
            $this->auditLogger->info(
                sprintf(
                    '%s: %s (ID: %d) by %s',
                    $action,
                    $entityType,
                    $entityId,
                    $user ? $user->getUserIdentifier() : 'anonymous'
                ),
                [
                    'action' => $action,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'user_id' => $user ? $user->getUserIdentifier() : null,
                    'old_values' => $oldValues,
                    'new_values' => $newValues,
                    'description' => $description,
                ]
            );
        } catch (\Exception $e) {
            // Log error but don't break the application
            $this->auditLogger->error('Failed to create audit log', [
                'exception' => $e->getMessage(),
                'action' => $action,
            ]);
        }
    }

    /**
     * Get entity ID using reflection.
     *
     * @param object $entity
     * @return int
     */
    private function getEntityId(object $entity): int
    {
        $reflection = new \ReflectionClass($entity);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        return $idProperty->getValue($entity) ?? 0;
    }

    /**
     * Extract entity values for logging.
     *
     * @param object $entity
     * @return array
     */
    public function extractEntityValues(object $entity): array
    {
        $values = [];
        $reflection = new \ReflectionClass($entity);

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $name = $property->getName();

            // Skip certain properties
            if (in_array($name, ['id', 'password', 'roles', 'createdAt', 'updatedAt'])) {
                continue;
            }

            $value = $property->getValue($entity);

            // Convert objects to string representation
            if (is_object($value)) {
                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format('Y-m-d H:i:s');
                } elseif (method_exists($value, 'getId')) {
                    $value = $value->getId();
                } else {
                    $value = (string) $value;
                }
            }

            $values[$name] = $value;
        }

        return $values;
    }
}

