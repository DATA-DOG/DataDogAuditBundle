<?php

declare(strict_types=1);

namespace DataDog\AuditBundle\Tests\EventListener;

use DataDog\AuditBundle\Entity\AuditLog;
use DataDog\AuditBundle\Tests\Entity\Account;
use DataDog\AuditBundle\Tests\OrmTestCase;
use DataDog\AuditBundle\Tests\TestKernel;

final class AuditListenerExtendsVersionTest extends OrmTestCase
{
    protected function setUp(): void
    {
    }

    /**
     * @dataProvider dataProvider
     */
    public function testExtendsVersion(
        array $dataDogAuditConfig,
        callable $callable,
        array $expectedFields
    ): void {
        $this->bootKernel([
            'entities' => [
                Account::class => $dataDogAuditConfig,
            ],
        ]);

        $this->loadFixtures();

        $em = $this->getDoctrine()->getManager();

        $account = new Account();
        $callable($account);

        $em->persist($account);
        $em->flush();

        $auditLogs = $em->createQuery('SELECT l FROM '.AuditLog::class.' l')->getResult();
        $this->assertCount(1, $auditLogs);

        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $auditLogs[0]->getDiff());
        }
    }

    public static function dataProvider(): array
    {
        return [
            'exclude-password-field' => [
                [
                    'mode' => 'exclude',
                    'fields' => [
                        'password',
                    ],
                ],
                function (Account $account): void {
                    $account->setUsername('username');
                    $account->setPassword('password');
                },
                [
                    'username',
                ],
            ],
            'exclude-all' => [
                [
                    'mode' => 'exclude',
                    'fields' => null,
                ],
                function (Account $account): void {
                    $account->setUsername('username');
                    $account->setPassword('password');
                },
                [],
            ],
            'include-password-field' => [
                [
                    'mode' => 'include',
                    'fields' => [
                        'password',
                    ],
                ],
                function (Account $account): void {
                    $account->setUsername('username');
                    $account->setPassword('password');
                },
                [
                    'password',
                ],
            ],
            'include-all' => [
                [
                    'mode' => 'include',
                    'fields' => null,
                ],
                function (Account $account): void {
                    $account->setUsername('username');
                    $account->setPassword('password');
                },
                [
                    'username',
                    'password',
                ],
            ]
        ];
    }
}
