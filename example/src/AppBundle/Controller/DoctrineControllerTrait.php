<?php

namespace AppBundle\Controller;

use Doctrine\ORM\EntityManager;

/**
 * Trait DoctrineControllerTrait
 * @package AppBundle\Controller
 *
 * @method \Doctrine\Bundle\DoctrineBundle\Registry getDoctrine()
 */
trait DoctrineControllerTrait
{

    private function persist(...$entities)
    {
        foreach ($entities as $entity) {
            $this->getDoctrine()->getManager()->persist($entity);
        }
    }

    private function flush($class = null)
    {
        $this->getDoctrine()->getManager()->flush($class);
    }

    private function remove(...$entities)
    {
        foreach ($entities as $entity) {
            $this->getDoctrine()->getManager()->remove($entity);
        }
    }

    /**
     * @param $class
     * @return \Doctrine\Common\Persistence\ObjectRepository|EntityManager
     */
    private function repo($class)
    {
        return $this->getDoctrine()->getManager()->getRepository($class);
    }
}
