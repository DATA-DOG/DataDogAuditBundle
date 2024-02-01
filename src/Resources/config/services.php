<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use DataDog\AuditBundle\EventSubscriber\AuditSubscriber;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

return static function (ContainerConfigurator $container) {
    // @formatter:off
    $services = $container->services();
    $services
        ->set('datadog.event_subscriber.audit', AuditSubscriber::class)->private()
        ->arg(0, new Reference(TokenStorageInterface::class))
        //->tag('doctrine.event_subscriber')
        ->tag('doctrine.event_listener', ['event' => 'onFlush',])
    ;
    // @formatter:on
};
