<?php

declare(strict_types=1);

namespace DataDog\AuditBundle\Tests\EventListener;

use DataDog\AuditBundle\Entity\AuditLog;
use DataDog\AuditBundle\Tests\Entity\Post;
use DataDog\AuditBundle\Tests\Entity\Tag;
use DataDog\AuditBundle\Tests\OrmTestCase;
use DataDog\AuditBundle\Tests\TestKernel;

final class AuditListenerTest extends OrmTestCase
{
    public function testSingleEntityCreation(): void
    {
        $this->includeKernelBoot();
        $this->resetDatabase();

        $em = $this->getDoctrine()->getManager();

        $tag = new Tag();
        $tag->setName('Books');

        $em->persist($tag);
        $em->flush();

        $this->assertCount(1, $em->createQuery('SELECT l FROM '.AuditLog::class.' l')->getResult());
    }

    public function testSingleEntityUpdate(): void
    {
        $this->includeKernelBoot();
        $this->resetDatabase();

        $em = $this->getDoctrine()->getManager();

        $tag = new Tag();
        $tag->setName('Books');

        $em->persist($tag);
        $em->flush();

        $tag->setName('Movies');

        $em->flush();

        $this->assertCount(2, $em->createQuery('SELECT l FROM '.AuditLog::class.' l')->getResult());
    }

    public function testSingleEntityDelete(): void
    {
        $this->includeKernelBoot();
        $this->resetDatabase();

        $em = $this->getDoctrine()->getManager();

        $tag = new Tag();
        $tag->setName('Books');

        $em->persist($tag);
        $em->flush();

        $em->remove($tag);

        $em->flush();

        $this->assertCount(2, $em->createQuery('SELECT l FROM '.AuditLog::class.' l')->getResult());
    }

    public function testEntityRelationCreate(): void
    {
        $this->includeKernelBoot();
        $this->resetDatabase();

        $em = $this->getDoctrine()->getManager();

        $tag = new Tag();
        $tag->setName('Books');

        $post = new Post();
        $post->setTitle('Top 10 Books You Should Read');

        $post->addTag($tag);

        $em->persist($tag);
        $em->persist($post);
        $em->flush();

        $this->assertCount(3, $em->createQuery('SELECT l FROM '.AuditLog::class.' l')->getResult());
    }

    public function testEntityRelationUpdate(): void
    {
        $this->includeKernelBoot();
        $this->resetDatabase();

        $em = $this->getDoctrine()->getManager();

        $tag1 = new Tag();
        $tag1->setName('Books');

        $tag2 = new Tag();
        $tag2->setName('Lists');

        $post = new Post();
        $post->setTitle('Top 10 Books You Should Read');

        $post->addTag($tag1);

        $em->persist($tag1);
        $em->persist($tag2);
        $em->persist($post);
        $em->flush();

        $post->removeTag($tag1);
        $post->addTag($tag2);
        $em->flush();

        $this->assertCount(6, $em->createQuery('SELECT l FROM '.AuditLog::class.' l')->getResult());
    }

    public function testExcludeField(): void
    {
        $this->excludeKernelBoot();
        $this->resetDatabase();

        $em = $this->getDoctrine()->getManager();

        $tag = new Tag();
        $tag->setName('Books');

        $em->persist($tag);
        $em->flush();

        $tag->setName('Movies');

        $em->flush();

        $this->assertCount(2, $em->createQuery('SELECT l FROM '.AuditLog::class.' l')->getResult());
    }

    private function includeKernelBoot(): void
    {
        $this->bootKernel([
            'audited_entities' => [
                Tag::class,
                Post::class,
            ],
        ]);

        $this->loadFixtures();
    }

    private function excludeKernelBoot(): void
    {
        $this->bootKernel([
            'unaudited_entities' => [
                Tag::class,
            ],
        ]);

        $this->loadFixtures();
    }
}
