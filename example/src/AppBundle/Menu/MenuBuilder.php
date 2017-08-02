<?php

namespace AppBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

class MenuBuilder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param FactoryInterface $factory
     * @return \Knp\Menu\ItemInterface
     */
    public function top(FactoryInterface $factory)
    {
        $menu = $factory->createItem('root');
        $menu->setChildrenAttribute('class', 'nav navbar-nav pull-right');

        $child = function($label, $route) use($menu) {
            $attributes = ['role' => 'presentation'];
            $menu->addChild($label, compact('route', 'attributes'));
        };

        $child('Audit Log', 'audit');
        $child('Projects', 'projects');
        $user = $this->getUser();
        if ($user instanceof UserInterface) {
            $child('Logout ' . $user, 'logout');
        } else {
            $child('Login', 'users');
        }

        return $menu;
    }

    /**
     * @return UserInterface
     */
    private function getUser()
    {
        $token = $this->container->get('security.token_storage')->getToken();
        if (!$token instanceof TokenInterface) {
            return null;
        }

        return $token->getUser();
    }
}
