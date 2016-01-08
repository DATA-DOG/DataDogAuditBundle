<?php

namespace DataDog\AuditBundle\EventSubscriber;

use DataDog\AuditBundle\DBAL\AuditLogger;
use DataDog\AuditBundle\Entity\AuditLog;
use DataDog\AuditBundle\Entity\Association;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Logging\LoggerChain;
use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;

class AuditSubscriber implements EventSubscriber
{
    protected $labeler;

    /**
     * @var SQLLogger
     */
    private $old;

    /**
     * @var TokenStorage
     */
    protected $securityTokenStorage;

    private $inserted = []; // [$source, $changeset]
    private $updated = []; // [$source, $changeset]
    private $removed = []; // [$source, $id]
    private $associated = [];   // [$source, $target, $mapping]
    private $dissociated = []; // [$source, $target, $id, $mapping]

    private $assocInsertStmt;
    private $auditInsertStmt;

    public function __construct(TokenStorage $securityTokenStorage)
    {
        $this->securityTokenStorage = $securityTokenStorage;
    }

    public function setLabeler(callable $labeler = null)
    {
        $this->labeler = $labeler;
        return $this;
    }

    public function getLabeler()
    {
        return $this->labeler;
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        // extend the sql logger
        $this->old = $em->getConnection()->getConfiguration()->getSQLLogger();
        $new = new LoggerChain();
        $new->addLogger(new AuditLogger(function () use($em) {
            $this->flush($em);
        }));
        if ($this->old instanceof SQLLogger) {
            $new->addLogger($this->old);
        }
        $em->getConnection()->getConfiguration()->setSQLLogger($new);

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->updated[] = [$entity, $uow->getEntityChangeSet($entity)];
        }
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->inserted[] = [$entity, $ch = $uow->getEntityChangeSet($entity)];
        }
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $uow->initializeObject($entity);
            $this->removed[] = [$entity, $this->id($em, $entity)];
        }
        foreach ($uow->getScheduledCollectionUpdates() as $collection) {
            $mapping = $collection->getMapping();
            if (!$mapping['isOwningSide'] || $mapping['type'] !== ClassMetadataInfo::MANY_TO_MANY) {
                continue; // ignore inverse side or one to many relations
            }
            foreach ($collection->getInsertDiff() as $entity) {
                $this->associated[] = [$collection->getOwner(), $entity, $mapping];
            }
            foreach ($collection->getDeleteDiff() as $entity) {
                $this->dissociated[] = [$collection->getOwner(), $entity, $this->id($em, $entity), $mapping];
            }
        }
        foreach ($uow->getScheduledCollectionDeletions() as $collection) {
            $mapping = $collection->getMapping();
            if (!$mapping['isOwningSide'] || $mapping['type'] !== ClassMetadataInfo::MANY_TO_MANY) {
                continue; // ignore inverse side or one to many relations
            }
            foreach ($collection->toArray() as $entity) {
                $this->dissociated[] = [$collection->getOwner(), $entity, $this->id($em, $entity), $mapping];
            }
        }
    }

    private function flush(EntityManager $em)
    {
        $em->getConnection()->getConfiguration()->setSQLLogger($this->old);
        $uow = $em->getUnitOfWork();

        $auditPersister = $uow->getEntityPersister(AuditLog::class);
        $this->auditInsertStmt = $em->getConnection()->prepare($auditPersister->getInsertSql());
        $assocPersister = $uow->getEntityPersister(Association::class);
        $this->assocInsertStmt = $em->getConnection()->prepare($assocPersister->getInsertSql());

        foreach ($this->updated as $entry) {
            list($entity, $ch) = $entry;
            // the changeset might be updated from UOW extra updates
            $ch = array_merge($ch, $uow->getEntityChangeSet($entity));
            $this->update($em, $entity, $ch);
        }

        foreach ($this->inserted as $entry) {
            list($entity, $ch) = $entry;
            // the changeset might be updated from UOW extra updates
            $ch = array_merge($ch, $uow->getEntityChangeSet($entity));
            $this->insert($em, $entity, $ch);
        }

        foreach ($this->associated as $entry) {
            list($source, $target, $mapping) = $entry;
            $this->associate($em, $source, $target, $mapping);
        }

        foreach ($this->dissociated as $entry) {
            list($source, $target, $id, $mapping) = $entry;
            $this->dissociate($em, $source, $target, $id, $mapping);
        }

        foreach ($this->removed as $entry) {
            list($entity, $id) = $entry;
            $this->remove($em, $entity, $id);
        }

        $this->inserted = [];
        $this->updated = [];
        $this->removed = [];
        $this->associated = [];
        $this->dissociated = [];
    }

    private function associate(EntityManager $em, $source, $target, array $mapping)
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

    private function dissociate(EntityManager $em, $source, $target, $id, array $mapping)
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

    private function insert(EntityManager $em, $entity, array $ch)
    {
        $meta = $em->getClassMetadata(get_class($entity));
        $this->audit($em, [
            'action' => 'insert',
            'source' => $this->assoc($em, $entity),
            'target' => null,
            'blame' => $this->blame($em),
            'diff' => $this->diff($em, $entity, $ch),
            'tbl' => $meta->table['name'],
        ]);
    }

    private function update(EntityManager $em, $entity, array $ch)
    {
        $diff = $this->diff($em, $entity, $ch);
        if (!$diff) {
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

    private function remove(EntityManager $em, $entity, $id)
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

    private function audit(EntityManager $em, array $data)
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
            $this->assocInsertStmt->execute();
            // @TODO: for postgresql may need sequence
            $data[$field] = $c->lastInsertId();
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
                $typ = Type::getType(Type::BIGINT); // relation
            }
            if (in_array($name, ['source', 'target', 'blame']) && $data[$name] === false) {
                $data[$name] = null;
            }
            $this->auditInsertStmt->bindValue($idx++, $data[$name], $typ);
        }
        $this->auditInsertStmt->execute();
    }

    private function id(EntityManager $em, $entity)
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

    private function diff(EntityManager $em, $entity, array $ch)
    {
        $uow = $em->getUnitOfWork();
        $meta = $em->getClassMetadata(get_class($entity));
        $diff = [];
        foreach ($ch as $fieldName => list($old, $new)) {
            if ($meta->hasField($fieldName)) {
                $mapping = $meta->fieldMappings[$fieldName];
                $diff[$fieldName] = [
                    'old' => $this->value($em, Type::getType($mapping['type']), $old),
                    'new' => $this->value($em, Type::getType($mapping['type']), $new),
                    'col' => $mapping['columnName'],
                ];
            } elseif ($meta->hasAssociation($fieldName) && $meta->isSingleValuedAssociation($fieldName)) {
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
        return $diff;
    }

    private function assoc(EntityManager $em, $association = null)
    {
        if (null === $association) {
            return null;
        }
        $meta = $em->getClassMetadata(get_class($association));
        $res = ['class' => $meta->name, 'typ' => $this->typ($meta->name), 'tbl' => $meta->table['name']];
        $em->getUnitOfWork()->initializeObject($association); // ensure that proxies are initialized
        $res['fk'] = (string)$this->id($em, $association);
        $res['label'] = $this->label($em, $association);
        return $res;
    }

    private function typ($className)
    {
        // strip prefixes and repeating garbage from name
        $className = preg_replace("/^(.+\\\)?(.+)(Bundle\\\Entity)/", "$2", $className);
        // underscore and lowercase each subdirectory
        return implode('.', array_map(function($name) {
            return strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/', '_$1', $name));
        }, explode('\\', $className)));
    }

    private function label(EntityManager $em, $entity)
    {
        if (is_callable($this->labeler)) {
            return $this->labeler($entity);
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

    private function value(EntityManager $em, Type $type, $value)
    {
        $platform = $em->getConnection()->getDatabasePlatform();
        switch ($type->getName()) {
        case Type::BOOLEAN:
            return $type->convertToPHPValue($value, $platform); // json supports boolean values
        default:
            return $type->convertToDatabaseValue($value, $platform);
        }
    }

    protected function blame(EntityManager $em)
    {
        $token = $this->securityTokenStorage->getToken();
        if ($token && $token->getUser() instanceof UserInterface) {
            return $this->assoc($em, $token->getUser());
        }
        return null;
    }

    public function getSubscribedEvents()
    {
        return [Events::onFlush];
    }
}
