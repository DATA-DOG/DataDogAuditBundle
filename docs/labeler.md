# Setting a Labeler

Create a Labeler class/method.

`\App\Labler\AuditLabler`

```php
namespace App\Labler;

use Symfony\Component\Security\Core\User\UserInterface;

class AuditLabler
{
    public static function getLabel($entity): string
    {
        if ($entity instanceof UserInterface) {
            return $entity->getUserIdentifier();
        }

        return 'Unlabeled';
    }
}
```

Re-define the audit listener service to call the `setLabler` method of the `AuditListener` with the [callable](https://www.php.net/manual/en/language.types.callable.php).

`app/config/services.yml`

```yaml
services:
    data_dog_audit.listener.audit:
        class: 'DataDog\AuditBundle\EventListener\AuditListener'
        arguments: ['@security.token_storage']
        tags:
          - { name: doctrine.event_subscriber, connection: default }
        calls:
            - ['setLabeler', [['App\Labler\AuditLabler', 'getLabel']]]
```
