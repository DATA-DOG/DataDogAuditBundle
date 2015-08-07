<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\Tag;

class Tags implements OrderedFixtureInterface, FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $featured = new Tag();
        $featured->setName("Featured");
        $manager->persist($featured);

        $important = new Tag();
        $important->setName("Important");
        $manager->persist($important);

        $new = new Tag();
        $new->setName("New");
        $manager->persist($new);

        $openSource = new Tag();
        $openSource->setName("Open Source");
        $manager->persist($openSource);

        $manager->flush();
    }

    public function getOrder()
    {
        return 0;
    }
}
