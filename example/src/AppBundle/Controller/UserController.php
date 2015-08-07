<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\User;

class UserController extends Controller
{
    use DoctrineControllerTrait;

    /**
     * @Route("/list", name="users")
     * @Method("GET")
     * @Template
     */
    public function listAction()
    {
        $users = $this->repo('AppBundle:User')->findAll();
        return compact('users');
    }

    /**
     * @Route("/login/{id}", name="login")
     * @Method("GET")
     * @Template
     */
    public function loginAction(User $user)
    {
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->get('security.context')->setToken($token);
        return $this->redirect($this->generateUrl('projects'));
    }
}
