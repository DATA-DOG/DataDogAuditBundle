<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\Project;

class Projects implements OrderedFixtureInterface, FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $langs = $manager->getRepository('AppBundle:Language')
            ->createQueryBuilder('l')
            ->orderBy('l.code', 'ASC')
            ->getQuery()
            ->getResult();

        $tags = $manager->getRepository('AppBundle:Tag')
            ->createQueryBuilder('t')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();

        list($featured, $important, $new, $openSource) = $tags;
        list($go, $haskell, $php) = $langs;

        $pager = new Project();
        $pager->setCode("pg")
            ->setName("Pager for Symfony2")
            ->setLanguage($php)
            ->setDeadline(8)
            ->setHoursSpent(6)
            ->addTag($featured)
            ->addTag($openSource)
            ->addTag($new);
        $manager->persist($pager);

        $godog = new Project();
        $godog->setCode("godog")
            ->setName("Godog cucumber and behat like BDD framework for GO")
            ->setLanguage($go)
            ->setDeadline(60)
            ->setHoursSpent(80)
            ->addTag($openSource);
        $manager->persist($godog);

        $sqlmock = new Project();
        $sqlmock->setCode("sqlmock")
            ->setName("Sql driver mock for GO")
            ->setLanguage($go)
            ->setDeadline(60)
            ->setHoursSpent(40)
            ->addTag($openSource);
        $manager->persist($sqlmock);

        $xmonad = new Project();
        $xmonad->setCode('xmonad')
            ->setName("Tiling window manager")
            ->setLanguage($haskell)
            ->setDeadline(1500)
            ->setHoursSpent(9999)
            ->addTag($openSource);
        $manager->persist($xmonad);

        $manager->flush();
    }

    public function getOrder()
    {
        return 5;
    }
}
