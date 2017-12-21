#Setting a Labeler

Create a Labeler class/method.

\AppBundle\Labler\AuditLabler

    namespace AppBundle\Labler;
    
    use Symfony\Component\Security\Core\User\UserInterface;
    
    class AuditLabler
    {
        public static function getLabel( $entity) {
            if ($entity instanceof UserInterface) {
                return $entity->getUsername();
            }
    
            return 'Unlabeled';
        }
    }

Re-define the audit subscriber service to call the `setLabler` method of the AuditSubscriber with the [callable](http://php.net/manual/en/language.types.callable.php).

app/config/config.yml

    services:
        ...
        datadog.event_subscriber.audit:
            class: 'DataDog\AuditBundle\EventSubscriber\AuditSubscriber'
            arguments: ["@security.token_storage"]
            tags:
              - { name: doctrine.event_subscriber, connection: default }
            calls:
                - ['setLabeler', [['\AppBundle\Labler\AuditLabler', 'getLabel']]]
        ...
        
or define in your services file

app/config/services.xml
   
    ...
    <service id="datadog.event_subscriber.audit" class="DataDog\AuditBundle\EventSubscriber\AuditSubscriber">
        <argument type="service" id="security.token_storage"/>
        <tag name="doctrine.event_subscriber"/>
        <call method="setLabeler">
            <argument type="collection">
                <argument>\AppBundle\Labler\AuditLabler</argument>
                <argument>getLabel</argument>
            </argument>
        </call>
    </service>
    ...