<?php

declare(strict_types=1);

namespace DataDog\AuditBundle\Tests\EventSubscriber;

use DataDog\AuditBundle\Entity\AuditLog;
use DataDog\AuditBundle\Tests\Entity\Tag;
use DataDog\AuditBundle\Tests\OrmTestCase;

final class AuditSubscriberTest extends OrmTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures();
    }

    public function testCorrectNumberOfAuditLogs(): void
    {
        $this->resetDatabase();

        $em = $this->getDoctrine()->getManager();

        $tag1 = new Tag();
        $tag1->setName('Books');

        $em->persist($tag1);
        $em->flush();

        $tag1->setName('Movies');

        $em->flush();

        $this->assertCount(2, $em->createQuery('SELECT l FROM '.AuditLog::class.' l')->getResult());
    }
}
