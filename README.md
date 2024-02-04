# Audit Bundle

This bundle creates an audit log for all Doctrine ORM database related changes:

- Inserts and updates including their diffs and relation field diffs.
- Many to many relation changes, association and dissociation actions.
- If there is a user in token storage, they will be linked to the log.
- The audit entries are inserted within the same transaction during **flush**,
if something fails the state remains clean.

Basically you can track any change from these log entries if they were
managed through standard **ORM** operations.

**NOTE:** audit cannot track DQL or direct SQL updates or delete statement executions.

## Install

First, install it with composer:

```bash
composer require data-dog/audit-bundle
```

Then, add it in your bundles.

```php
// config/bundles.php
return [
    ...
    DataDog\AuditBundle\DataDogAuditBundle::class => ['all' => true],
    ...
];
```

Finally, create the database tables used by the bundle:

Using [Doctrine Migrations Bundle](https://symfony.com/bundles/DoctrineMigrationsBundle/current/index.html):

```bash
php app/console doctrine:migrations:diff
php app/console doctrine:migrations:migrate
```

Using Doctrine Schema:

```bash
php app/console doctrine:schema:update --force
```

## Usage

**audit** entities will be mapped automatically if you run schema update or similar.
And all the database changes will be reflected in the audit log afterwards.

### Unaudited Entities

Sometimes, you might not want to create audit log entries for particular entities.
You can achieve this by listing those entities under the `unaudited_entities` configuration
key in your `config.yml`, for example:

```yaml
data_dog_audit:
    unaudited_entities:
        - App\Entity\NoAuditForThis
```

### Specify Audited Entities

Sometimes, it is also possible, that you want to create audit log entries only for particular entities.
You can achieve it quite similar to unaudited entities. You can list them under the `audited_entities`
configuration key in your `config.yml`, for example:

```yaml
data_dog_audit:
    audited_entities:
        - App\Entity\AuditForThis
```

You can specify either audited or unaudited entities. If both are specified, only audited entities would be taken into account.

### Impersonation

Sometimes, you might also want to blame the `impersonator` user instead of the `impersonated` one.
You can archive this by adding the `blame_impersonator` configuration key in your `config.yml`, for example:

```yaml
    data_dog_audit:
        blame_impersonator: true
```

The default behavior is to blame the logged-in user, so it will ignore the `impersonator` when not explicitly declared.

## Clean old logs

To clean logs older than 3 months, use the following command:

```bin/console audit-logs:delete-old-logs```


## License

The audit bundle is free to use and is licensed under the [MIT license](https://opensource.org/licenses/mit-license.php)
