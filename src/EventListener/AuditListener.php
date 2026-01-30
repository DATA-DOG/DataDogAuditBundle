<?php

namespace DataDog\AuditBundle\EventListener;

use DataDog\AuditBundle\DBAL\Middleware\AuditFlushMiddleware;
use DataDog\AuditBundle\Entity\Association;
use DataDog\AuditBundle\Entity\AuditLog;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\SwitchUserRole;
use Symfony\Component\Security\Core\User\UserInterface;

class AuditListener
{
    /**
     * @var callable|null
     */
    protected $labeler;

    protected array $auditedEntities = [];

    protected array $unauditedEntities = [];

    protected array $entities = [];

    protected bool $blameImpersonator = false;

    protected array $inserted = []; // [$source, $changeset]

    protected array $updated = []; // [$source, $changeset]

    protected array $removed = []; // [$source, $id]

    protected array $associated = [];   // [$source, $target, $mapping]

    protected array $dissociated = []; // [$source, $target, $id, $mapping]

    protected $assocInsertStmt;

    protected $auditInsertStmt;

    protected ?UserInterface $blameUser = null;

    protected array $middlewares;

    public function __construct(
        private readonly TokenStorageInterface $securityTokenStorage,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function setLabeler(?callable $labeler = null): self
    {
        $this->labeler = $labeler;

        return $this;
    }

    public function getLabeler(): ?callable
    {
        return $this->labeler;
    }

    public function addAuditedEntities(array $auditedEntities): void
    {
        // use entity names as array keys for easier lookup
        foreach ($auditedEntities as $auditedEntity) {
            $this->auditedEntities[$auditedEntity] = true;
        }
    }

    public function addUnauditedEntities(array $unauditedEntities): void
    {
        // use entity names as array keys for easier lookup
        foreach ($unauditedEntities as $unauditedEntity) {
            $this->unauditedEntities[$unauditedEntity] = true;
        }
    }

    public function addEntities(array $entities): void
    {
        $this->entities = $entities;

        $auditedEntities = [];
        $unauditedEntities = [];

        foreach ($entities as $fqcn => $data) {
            if (
                $data['mode'] === 'include' ||
                ($data['mode'] === 'exclude' && !empty($data['fields']))
            ) {
                $auditedEntities[] = $fqcn;
            } elseif ($data['mode'] === 'exclude' && empty($data['fields'])) {
                $unauditedEntities[] = $fqcn;
            }
        }
        $this->addAuditedEntities($auditedEntities);
        $this->addUnauditedEntities($unauditedEntities);
    }

    public function setBlameImpersonator(bool $blameImpersonator): void
    {
        // blame impersonator user instead of logged user (where applicable)
        $this->blameImpersonator = $blameImpersonator;
    }

    public function getUnauditedEntities(): array
    {
        return array_keys($this->unauditedEntities);
    }

    protected function isEntityUnaudited(object $entity): bool
    {
        if (!empty($this->auditedEntities)) {
            // only selected entities are audited
            $isEntityUnaudited = true;
            foreach (array_keys($this->auditedEntities) as $auditedEntity) {
                if ($entity instanceof $auditedEntity) {
                    $isEntityUnaudited = false;
                    break;
                }
            }
        } else {
            $isEntityUnaudited = false;
            foreach (array_keys($this->unauditedEntities) as $unauditedEntity) {
                if ($entity instanceof $unauditedEntity) {
                    $isEntityUnaudited = true;
                    break;
                }
            }
        }

        return $isEntityUnaudited;
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $this->entityManager;
        $uow = $em->getUnitOfWork();

        $middlewares = $this->entityManager->getConnection()->getConfiguration()->getMiddlewares();
        foreach ($middlewares as $middleware) {
            if ($middleware::class === AuditFlushMiddleware::class) {
                $middleware->flushHandler = [$this, 'flush'];
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($this->isEntityUnaudited($entity)) {
                continue;
            }
            $this->updated[] = [$entity, $uow->getEntityChangeSet($entity)];
        }
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($this->isEntityUnaudited($entity)) {
                continue;
            }
            $this->inserted[] = [$entity, $ch = $uow->getEntityChangeSet($entity)];
        }
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($this->isEntityUnaudited($entity)) {
                continue;
            }
            $uow->initializeObject($entity);
            $this->removed[] = [$entity, $this->id($em, $entity)];
        }
        foreach ($uow->getScheduledCollectionUpdates() as $collection) {
            if ($this->isEntityUnaudited($collection->getOwner())) {
                continue;
            }
            $mapping = $collection->getMapping();
            if (!$mapping['isOwningSide'] || $mapping['type'] !== ClassMetadata::MANY_TO_MANY) {
                continue; // ignore inverse side or one to many relations
            }
            // For backward compatibility:
            // If $mapping is not already an array, convert it to an array.
            if (!is_array($mapping)) {
                $mapping = $mapping->toArray();
            }
            foreach ($collection->getInsertDiff() as $entity) {
                if ($this->isEntityUnaudited($entity)) {
                    continue;
                }
                $this->associated[] = [$collection->getOwner(), $entity, $mapping];
            }
            foreach ($collection->getDeleteDiff() as $entity) {
                if ($this->isEntityUnaudited($entity)) {
                    continue;
                }
                $this->dissociated[] = [$collection->getOwner(), $entity, $this->id($em, $entity), $mapping];
            }
        }
        foreach ($uow->getScheduledCollectionDeletions() as $collection) {
            if ($this->isEntityUnaudited($collection->getOwner())) {
                continue;
            }
            $mapping = $collection->getMapping();
            if (!is_array($mapping)) {
                $mapping = $mapping->toArray();
            }
            if (!$mapping['isOwningSide'] || $mapping['type'] !== ClassMetadata::MANY_TO_MANY) {
                continue; // ignore inverse side or one to many relations
            }
            foreach ($collection->toArray() as $entity) {
                if ($this->isEntityUnaudited($entity)) {
                    continue;
                }
                $this->dissociated[] = [$collection->getOwner(), $entity, $this->id($em, $entity), $mapping];
            }
        }
    }

    public function flush(): void
    {
        $uow = $this->entityManager->getUnitOfWork();

        $auditPersister = $uow->getEntityPersister(AuditLog::class);
        $rmAuditInsertSQL = new \ReflectionMethod($auditPersister, 'getInsertSQL');
        $this->auditInsertStmt = $this->entityManager->getConnection()->prepare($rmAuditInsertSQL->invoke($auditPersister));
        $assocPersister = $uow->getEntityPersister(Association::class);
        $rmAssocInsertSQL = new \ReflectionMethod($assocPersister, 'getInsertSQL');
        $this->assocInsertStmt = $this->entityManager->getConnection()->prepare($rmAssocInsertSQL->invoke($assocPersister));

        foreach ($this->updated as $entry) {
            list($entity, $ch) = $entry;
            // the changeset might be updated from UOW extra updates
            $ch = array_merge($ch, $uow->getEntityChangeSet($entity));
            $this->update($this->entityManager, $entity, $ch);
        }

        foreach ($this->inserted as $entry) {
            list($entity, $ch) = $entry;
            // the changeset might be updated from UOW extra updates
            $ch = array_merge($ch, $uow->getEntityChangeSet($entity));
            $this->insert($this->entityManager, $entity, $ch);
        }

        foreach ($this->associated as $entry) {
            list($source, $target, $mapping) = $entry;
            $this->associate($this->entityManager, $source, $target, $mapping);
        }

        foreach ($this->dissociated as $entry) {
            list($source, $target, $id, $mapping) = $entry;
            $this->dissociate($this->entityManager, $source, $target, $id, $mapping);
        }

        foreach ($this->removed as $entry) {
            list($entity, $id) = $entry;
            $this->remove($this->entityManager, $entity, $id);
        }

        $this->inserted = [];
        $this->updated = [];
        $this->removed = [];
        $this->associated = [];
        $this->dissociated = [];
    }

    protected function associate(EntityManager $em, ?object $source, ?object $target, array $mapping): void
    {
        $this->audit($em, [
            'source' => $this->assoc($em, $source),
            'target' => $this->assoc($em, $target),
            'action' => 'associate',
            'blame' => $this->blame($em),
            'diff' => null,
            'tbl' => $mapping['joinTable']['name'],
        ]);
    }

    protected function dissociate(EntityManager $em, ?object $source, ?object $target, string|int $id, array $mapping): void
    {
        $this->audit($em, [
            'source' => $this->assoc($em, $source),
            'target' => array_merge($this->assoc($em, $target), ['fk' => $id]),
            'action' => 'dissociate',
            'blame' => $this->blame($em),
            'diff' => null,
            'tbl' => $mapping['joinTable']['name'],
        ]);
    }

    protected function insert(EntityManager $em, object $entity, array $ch): void
    {
        $diff = $this->diff($em, $entity, $ch);
        if (empty($diff)) {
            return; // if there is no entity diff, do not log it
        }
        $meta = $em->getClassMetadata(get_class($entity));
        $this->audit($em, [
            'action' => 'insert',
            'source' => $this->assoc($em, $entity),
            'target' => null,
            'blame' => $this->blame($em),
            'diff' => $diff,
            'tbl' => $meta->table['name'],
        ]);
    }

    protected function update(EntityManager $em, object $entity, array $ch): void
    {
        $diff = $this->diff($em, $entity, $ch);
        if (empty($diff)) {
            return; // if there is no entity diff, do not log it
        }
        $meta = $em->getClassMetadata(get_class($entity));
        $this->audit($em, [
            'action' => 'update',
            'source' => $this->assoc($em, $entity),
            'target' => null,
            'blame' => $this->blame($em),
            'diff' => $diff,
            'tbl' => $meta->table['name'],
        ]);
    }

    protected function remove(EntityManager $em, object $entity, string|int $id): void
    {
        $meta = $em->getClassMetadata(get_class($entity));
        $source = array_merge($this->assoc($em, $entity), ['fk' => $id]);
        $this->audit($em, [
            'action' => 'remove',
            'source' => $source,
            'target' => null,
            'blame' => $this->blame($em),
            'diff' => null,
            'tbl' => $meta->table['name'],
        ]);
    }

    protected function audit(EntityManager $em, array $data): void
    {
        $c = $em->getConnection();
        $p = $c->getDatabasePlatform();
        $q = $em->getConfiguration()->getQuoteStrategy();

        foreach (['source', 'target', 'blame'] as $field) {
            if (null === $data[$field]) {
                continue;
            }
            $meta = $em->getClassMetadata(Association::class);
            $idx = 1;
            foreach ($meta->reflFields as $name => $f) {
                if ($meta->isIdentifier($name)) {
                    continue;
                }
                $typ = $meta->fieldMappings[$name]['type'];

                $this->assocInsertStmt->bindValue($idx++, $data[$field][$name], $typ);
            }
            $this->assocInsertStmt->executeQuery();
            // use id generator, it will always use identity strategy, since our
            // audit association explicitly sets that.
            $data[$field] = $meta->idGenerator->generateId($em, null);
        }

        $meta = $em->getClassMetadata(AuditLog::class);
        $data['loggedAt'] = new \DateTime();
        $idx = 1;
        foreach ($meta->reflFields as $name => $f) {
            if ($meta->isIdentifier($name)) {
                continue;
            }
            if (isset($meta->fieldMappings[$name]['type'])) {
                $typ = $meta->fieldMappings[$name]['type'];
            } else {
                $typ = Type::getType(Types::BIGINT); // relation
            }
            // @TODO: this check may not be necessary, simply it ensures that empty values are nulled
            if (in_array($name, ['source', 'target', 'blame']) && $data[$name] === false) {
                $data[$name] = null;
            }
            $this->auditInsertStmt->bindValue($idx++, $data[$name], $typ);
        }
        $this->auditInsertStmt->executeQuery();
    }

    protected function id(EntityManager $em, object $entity)
    {
        $meta = $em->getClassMetadata(get_class($entity));
        $pk = $meta->getSingleIdentifierFieldName();
        $pk = $this->value(
            $em,
            Type::getType($meta->fieldMappings[$pk]['type']),
            $meta->getReflectionProperty($pk)->getValue($entity)
        );

        return $pk;
    }

    protected function isDiff(object $entity, string $fieldName): bool
    {
        if (array_key_exists($entity::class, $this->entities)) { // New logic
            $entityConfig = $this->entities[$entity::class];

            return (
                (
                    $entityConfig['mode'] === 'include' &&
                    (
                        !empty($entityConfig['fields']) && in_array($fieldName, $entityConfig['fields']) ||
                        empty($entityConfig['fields'])
                    )
                ) ||
                (
                    $entityConfig['mode'] === 'exclude' &&
                    !empty($entityConfig['fields']) && !in_array($fieldName, $entityConfig['fields'])
                )
            );
        } else { // Old logic
            return true;
        }
    }

    protected function diff(EntityManager $em, object $entity, array $ch): array
    {
        $uow = $em->getUnitOfWork();
        $meta = $em->getClassMetadata(get_class($entity));
        $diff = [];
        foreach ($ch as $fieldName => list($old, $new)) {
            if ($meta->hasField($fieldName) && !array_key_exists($fieldName, $meta->embeddedClasses)) {
                if ($this->isDiff($entity, $fieldName)) {
                    $mapping = $meta->fieldMappings[$fieldName];

                    $diff[$fieldName] = [
                        'old' => $this->value($em, Type::getType($mapping['type']), $old),
                        'new' => $this->value($em, Type::getType($mapping['type']), $new),
                        'col' => $mapping['columnName'],
                    ];
                }
            } elseif ($meta->hasAssociation($fieldName) && $meta->isSingleValuedAssociation($fieldName)) {
                if ($this->isDiff($entity, $fieldName)) {
                    $mapping = $meta->associationMappings[$fieldName];
                    $colName = $meta->getSingleAssociationJoinColumnName($fieldName);
                    $assocMeta = $em->getClassMetadata($mapping['targetEntity']);

                    $diff[$fieldName] = [
                        'old' => $this->assoc($em, $old),
                        'new' => $this->assoc($em, $new),
                        'col' => $colName,
                    ];
                }
            }
        }

        return $diff;
    }

    protected function assoc(EntityManager $em, ?object $association = null): ?array
    {
        if (null === $association) {
            return null;
        }

        $meta = $em->getClassMetadata(get_class($association))->getName();
        $res = ['class' => $meta, 'typ' => $this->typ($meta), 'tbl' => null, 'label' => null];

        try {
            $meta = $em->getClassMetadata($meta);
            $res['tbl'] = $meta->table['name'];
            $em->getUnitOfWork()->initializeObject($association); // ensure that proxies are initialized
            $res['fk'] = (string)$this->id($em, $association);
            $res['label'] = $this->label($em, $association);
        } catch (\Exception $e) {
            $res['fk'] = (string)$association->getId();
        }

        return $res;
    }

    protected function typ($className): string
    {
        // strip prefixes and repeating garbage from name
        $className = preg_replace("/^(.+\\\)?(.+)(Bundle\\\Entity)/", "$2", $className);

        // underscore and lowercase each subdirectory
        return implode('.', array_map(function ($name) {
            return strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/', '_$1', $name));
        }, explode('\\', $className)));
    }

    protected function label(EntityManager $em, object $entity)
    {
        if (is_callable($this->labeler)) {
            return call_user_func($this->labeler, $entity);
        }
        $meta = $em->getClassMetadata(get_class($entity));
        switch (true) {
            case $meta->hasField('title'):
                return $meta->getReflectionProperty('title')->getValue($entity);
            case $meta->hasField('name'):
                return $meta->getReflectionProperty('name')->getValue($entity);
            case $meta->hasField('label'):
                return $meta->getReflectionProperty('label')->getValue($entity);
            case $meta->getReflectionClass()->hasMethod('__toString'):
                return (string)$entity;
            default:
                return "Unlabeled";
        }
    }

    protected function value(EntityManager $em, Type $type, $value)
    {
        // json_encode will error when trying to encode a resource
        if (is_resource($value)) {
            // https://stackoverflow.com/questions/26303513/getting-blob-type-doctrine-entity-property-returns-data-only-once/26306571
            if (0 !== ftell($value)) {
                rewind($value);
            }

            $value = stream_get_contents($value);
        }

        $platform = $em->getConnection()->getDatabasePlatform();
        switch ($type->getBindingType()) {
            case Types::BOOLEAN:
                return $type->convertToPHPValue($value, $platform); // json supports boolean values
            default:
                return $type->convertToDatabaseValue($value, $platform);
        }
    }

    protected function blame(EntityManager $em): ?array
    {
        if ($this->blameUser instanceof UserInterface && \method_exists($this->blameUser, 'getId')) {
            return $this->assoc($em, $this->blameUser);
        }
        $token = $this->securityTokenStorage->getToken();
        $impersonatorUser = $this->getImpersonatorUserFromSecurityToken($token);
        if ($impersonatorUser instanceof UserInterface) {
            return $this->assoc($em, $impersonatorUser);
        }
        if ($token && $token->getUser() instanceof UserInterface && \method_exists($token->getUser(), 'getId')) {
            return $this->assoc($em, $token->getUser());
        }

        return null;
    }

    private function getImpersonatorUserFromSecurityToken(?TokenInterface $token)
    {
        if (false === $this->blameImpersonator) {
            return null;
        }
        if (!$token instanceof TokenInterface) {
            return null;
        }

        foreach ($this->getRoles($token) as $role) {
            if ($role instanceof SwitchUserRole) {
                return $role->getSource()->getUser();
            }
        }

        return null;
    }

    private function getRoles(TokenInterface $token): array
    {
        if (method_exists($token, 'getRoleNames')) {
            return $token->getRoleNames();
        }

        return $token->getRoles();
    }

    public function setBlameUser(UserInterface $user): void
    {
        $this->blameUser = $user;
    }
}
