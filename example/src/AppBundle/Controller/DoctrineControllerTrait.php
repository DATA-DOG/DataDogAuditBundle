<?php

namespace AppBundle\Controller;

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

    private function repo($class)
    {
        return $this->getDoctrine()->getManager()->getRepository($class);
    }
}
