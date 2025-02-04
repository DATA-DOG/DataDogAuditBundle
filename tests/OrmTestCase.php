<?php

declare(strict_types=1);

namespace DataDog\AuditBundle\Tests;

use DataDog\AuditBundle\Tests\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class OrmTestCase extends TestCase
{
    protected KernelInterface $kernel;

    protected function bootKernel(array $dataDogAuditConfig): void
    {
        $this->kernel = new TestKernel($dataDogAuditConfig);
        $this->kernel->boot();
    }

    protected function getKernel(): KernelInterface
    {
        return $this->kernel;
    }

    protected function getDoctrine(): Registry
    {
        return $this->getKernel()->getContainer()->get('doctrine');
    }

    protected function resetDatabase(): void
    {
        $em = $this->getDoctrine()->getManager();
        $em->clear();

        $metadata = $em->getMetadataFactory()->getAllMetadata();

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected function loadFixtures(): void
    {
        $this->resetDatabase();
        $this->persistFixtureData();

        $this->getDoctrine()->getManager()->flush();
    }

    protected function persistFixtureData(): void
    {
        $em = $this->getDoctrine()->getManager();

        $tag1 = new Tag();
        $tag1->setName('Books');

        $em->persist($tag1);
    }
}
