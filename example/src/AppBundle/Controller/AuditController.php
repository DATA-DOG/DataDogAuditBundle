<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\QueryBuilder;
use DataDog\PagerBundle\Pagination;
use DataDog\AuditBundle\Entity\AuditLog;

class AuditController extends Controller
{
    use DoctrineControllerTrait;

    public function filters(QueryBuilder $qb, $key, $val)
    {
        switch ($key) {
        case 'history':
            if ($val) {
                $orx = $qb->expr()->orX();
                $orx->add('s.fk = :fk');
                $orx->add('t.fk = :fk');

                $qb->andWhere($orx);
                $qb->setParameter('fk', intval($val));
            }
            break;
        case 'class':
            $orx = $qb->expr()->orX();
            $orx->add('s.class = :class');
            $orx->add('t.class = :class');

            $qb->andWhere($orx);
            $qb->setParameter('class', $val);
            break;
        case 'blamed':
            if ($val === 'null') {
                $qb->andWhere($qb->expr()->isNull('a.blame'));
            } else {
                // this allows us to safely ignore empty values
                // otherwise if $qb is not changed, it would add where the string is empty statement.
                $qb->andWhere($qb->expr()->eq('b.fk', ':blame'));
                $qb->setParameter('blame', $val);
            }
            break;
        default:
            // if user attemps to filter by other fields, we restrict it
            throw new \Exception("filter not allowed");
        }
    }

    /**
     * @Method("GET")
     * @Template
     * @Route("/audit", name="audit")
     */
    public function indexAction(Request $request)
    {
        $this->someActions();
        Pagination::$defaults = array_merge(Pagination::$defaults, ['limit' => 25]);

        $qb = $this->repo("DataDogAuditBundle:AuditLog")
            ->createQueryBuilder('a')
            ->addSelect('s', 't', 'b')
            ->innerJoin('a.source', 's')
            ->leftJoin('a.target', 't')
            ->leftJoin('a.blame', 'b');

        $options = [
            'sorters' => ['a.loggedAt' => 'DESC'],
            'applyFilter' => [$this, 'filters'],
        ];

        $sourceClasses = [
            Pagination::$filterAny => 'Any Source Class',
        ];

        foreach ($this->getDoctrine()->getManager()->getMetadataFactory()->getAllMetadata() as $meta) {
            if ($meta->isMappedSuperclass || strpos($meta->name, 'DataDog\AuditBundle') === 0) {
                continue;
            }
            $parts = explode('\\', $meta->name);
            $sourceClasses[$meta->name] = end($parts);
        }

        $users = [
            Pagination::$filterAny => 'Any User',
            'null' => 'Unknown',
        ];
        foreach ($this->repo('AppBundle:User')->findAll() as $user) {
            $users[$user->getId()] = (string) $user;
        }

        $logs = new Pagination($qb, $request, $options);
        return compact('logs', 'sourceClasses', 'users');
    }

    /**
     * @Route("/audit/diff/{id}")
     * @Method("GET")
     * @Template
     */
    public function diffAction(AuditLog $log)
    {
        return compact('log');
    }

    private function someActions()
    {
        $tag = $this->repo('AppBundle:Tag')->findOneByName('New');
        if (!$tag) {
            return; // already performed
        }

        $user = $this->repo('AppBundle:User')->findOneByUsername('luke');
        $old = $this->get('security.token_storage')->getToken();
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->get('security.token_storage')->setToken($token);


        $pager = $this->repo('AppBundle:Project')->findOneByCode('pg');
        $pager->removeTag($tag);
        $this->persist($pager);
        $this->remove($tag);

        $godog = $this->repo('AppBundle:Project')->findOneByCode('godog');
        $godog->setName('Godog BDD framework');
        $godog->setHoursSpent(85);
        $this->persist($godog);

        $this->flush();
        $this->get('security.token_storage')->setToken($old);
    }
}
