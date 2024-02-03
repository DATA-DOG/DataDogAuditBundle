<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use DataDog\AuditBundle\EventListener\AuditListener;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

return static function (ContainerConfigurator $container) {
    // default configuration for services in *this* file
    $services = $container->services()
        ->defaults()
        ->autowire()      // Automatically injects dependencies in your services.
        ->autoconfigure() // Automatically registers your services as commands, event subscribers, etc.
    ;

    // makes classes in src/ available to be used as services
    // this creates a service per class whose id is the fully-qualified class name
    $services->load('DataDog\\AuditBundle\\', '../../../src/')
        ->exclude('../../../src/{DependencyInjection,Entity,Resources,DataDogAuditBundle.php}');


//    // @formatter:off
//    $services = $container->services();
//    $services
//        ->set('datadog.event_listener.audit', AuditListener::class)->private()
//        ->arg(0, new Reference(TokenStorageInterface::class))
//        //->tag('doctrine.event_subscriber')
//        ->tag('doctrine.event_listener', ['event' => 'onFlush',])
//    ;
//    // @formatter:on
};
