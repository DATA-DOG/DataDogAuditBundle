<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\Language;

class Languages implements OrderedFixtureInterface, FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $php = new Language();
        $php->setCode("php")->setName("PHP");
        $manager->persist($php);

        $go = new Language();
        $go->setCode("go")->setName("Golang");
        $manager->persist($go);

        $haskell = new Language();
        $haskell->setCode("hs")->setName("Haskell");
        $manager->persist($haskell);

        $manager->flush();
    }

    public function getOrder()
    {
        return 0;
    }
}
