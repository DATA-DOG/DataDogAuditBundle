<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\QueryBuilder;
use DataDog\PagerBundle\Pagination;

class AuditController extends Controller
{
    use DoctrineControllerTrait;

    public function filters(QueryBuilder $qb, $key, $val)
    {
        switch ($key) {
        case 'blamed':
            if ($val === 'null') {
                $qb->andWhere($qb->expr()->isNull('a.blame'));
            } else {
                // this allows us to safely ignore empty values
                // otherwise if $qb is not changed, it would add where the string is empty statement.
                $qb->andWhere($qb->expr()->eq('b.pk', $val));
            }
            break;
        case 's.class':
            return; // we allow this filter
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
            'AppBundle\Entity\Project' => 'Project',
            'AppBundle\Entity\Tag' => 'Tag',
            'AppBundle\Entity\Language' => 'Language',
            'AppBundle\Entity\User' => 'User',
        ];

        $users = [
            Pagination::$filterAny => 'Any User',
            'null' => 'Unknown',
        ];
        foreach ($this->repo('AppBundle:User')->findAll() as $user) {
            $users[$user->getId()] = (string)$user;
        }

        $logs = new Pagination($qb, $request, $options);
        return compact('logs', 'sourceClasses', 'users');
    }
}
