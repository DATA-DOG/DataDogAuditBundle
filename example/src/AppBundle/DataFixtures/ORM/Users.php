<?php

namespace AppBundle\DataFixtures\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\User;

class Users implements ContainerAwareInterface, FixtureInterface, OrderedFixtureInterface
{
    private $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 0;
    }

    /**
     * @param ObjectManager $em
     */
    function load(ObjectManager $em)
    {
        $users = ['joda', 'luke'];
        foreach ($users as $username) {
            $user = new User();
            $user->setFirstname(ucfirst($username));
            $user->setUsername($username);

            $passwd = $this->container->get('security.password_encoder')->encodePassword($user, 'secret');
            $user->setPassword($passwd);

            $em->persist($user);
        }
        $em->flush();
    }
}
