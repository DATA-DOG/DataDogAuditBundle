<?php

namespace AppBundle\Twig;

use DataDog\AuditBundle\Entity\AuditLog;

class AuditExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        $defaults = [
            'is_safe' => ['html'],
            'needs_environment' => true,
        ];

        $audit = new \Twig_Function_Method($this, 'audit', $defaults);
        $audit_value = new \Twig_Function_Method($this, 'value', $defaults);

        return compact('audit', 'audit_value');
    }

    public function audit(\Twig_Environment $twig, AuditLog $log)
    {
        return $twig->render("AppBundle::Audit/{$log->getAction()}.html.twig", compact('log'));
    }

    public function value(\Twig_Environment $twig, $val)
    {
        if (is_bool($val)) {
            return $val ? 'true' : 'false';
        } elseif (is_array($val)) {
            return $twig->render("AppBundle::Audit/association.html.twig", compact('val'));
        }
        return $val;
    }

    public function getName()
    {
        return 'app_audit_extension';
    }
}
