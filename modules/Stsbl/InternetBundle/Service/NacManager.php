<?php

declare(strict_types=1);

namespace Stsbl\InternetBundle\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;
use IServ\CoreBundle\Entity\Group;
use IServ\CoreBundle\Entity\User;
use IServ\CoreBundle\Exception\ShellExecException;
use IServ\CoreBundle\Service\Logger;
use IServ\CoreBundle\Service\Shell;
use IServ\CoreBundle\Service\User\UserStorageInterface;
use IServ\CrudBundle\Entity\FlashMessageBag;
use IServ\HostBundle\Entity\Host;
use IServ\Library\Zeit\Zeit;
use Stsbl\InternetBundle\Entity\Nac;
use Stsbl\InternetBundle\Form\Data\CreateNacs;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

/*
 * The MIT License
 *
 * Copyright 2021 Felix Jacobi.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Service for NAC handling
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
final class NacManager
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Shell
     */
    private $shell;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var UserStorageInterface
     */
    private $userStorage;

    /**
     * @var array
     */
    private $nacWarnings = [];

    public function __construct(RequestStack $requestStack, Shell $shell, ManagerRegistry $doctrine, Logger $logger, UserStorageInterface $userStorage)
    {
        $this->em = $doctrine->getManager();
        $this->request = $requestStack->getCurrentRequest();
        $this->shell = $shell;
        $this->connection = $doctrine->getConnection();
        $this->logger = $logger;
        $this->userStorage = $userStorage;
    }

    /**
     * Checks if user have a NAC.
     * If no user given, assume the current one.
     */
    public function hasNac(?User $user = null): bool
    {
        $nacRepository = $this->em->getRepository(Nac::class);

        return $nacRepository->hasNac($this->getUser($user));
    }

    /**
     * Get NAC for user.
     * If no user given, assume the current one.
     * Note, that will throw a \RuntimeException, if no NAC exists.
     */
    public function getUserNac(?User $user = null): Nac
    {
        $nacRepository = $this->em->getRepository(Nac::class);

        return $nacRepository->findNacByUser($this->getUser($user));
    }

    /**
     * Shortcut to determine user (return user from security handler, if user is null).
     */
    private function getUser(UserInterface $user = null): User
    {
        if (null === $user) {
            $user = $this->userStorage->getUser();
        }

        return $user;
    }

    /**
     * Check if internet access is already granted to the current client.
     */
    public function isInternetGranted(): bool
    {
        // ignore if host is not in host management
        if ($this->em->getRepository('IServHostBundle:Host')->findOneByIp($this->request->getClientIp()) === null) {
            return false;
        }

        $host = $this->em->getRepository(Host::class)->findOneBy(['internet' => true, 'overrideRoute' => null, 'ip' => $this->request->getClientIp()]);

        if (null !== $host) {
            return true;
        }

        return $this->em->getRepository(Host::class)->findOneBy(['overrideRoute' => true, 'ip' => $this->request->getClientIp()]) !== null;
    }

    /**
     * Get internet information (overrideUntil and overrideBy).
     */
    public function getInternetInformation(): array
    {
        /** @var Host $host */
        $host = $this->em->getRepository(Host::class)->findOneByIp($this->request->getClientIp());

        if (null === $host) {
            return [
                'until' => null,
                'by' => null,
            ];
        }

        return [
            'until' => $host->getOverrideUntil(),
            'by' => $this->em->getRepository(User::class)->findOneByUsername($host->getOverrideBy()),
        ];
    }

    public function isInternetDenied(): bool
    {
        return $this->em->getRepository(Host::class)->findOneBy(['overrideRoute' => false, 'ip' => $this->request->getClientIp()]) !== null;
    }

    /**
     * Runs inet_timer.
     *
     * @return FlashMessageBag
     */
    public function inetTimer(): FlashMessageBag
    {
        system("killall -q -SIGHUP -r '^inet_timer:' || " .
            "killall -q -SIGHUP 'inet_timer'", $err);

        if ($err) {
            return $this->shellMsgError('closefd', ['/usr/lib/iserv/inet_timer', '-d']);
        }

        return new FlashMessageBag();
    }

    /**
     * Execute a command and return a FlashMessageBag with STDERR
     * lines as error messages.
     * Similar to the original from HostManager, but only show
     * STDERR lines.
     *
     * @return FlashMessageBag STDERR content as FlashMessageBag
     */
    private function shellMsgError(string $cmd, ?array $args = null, ?string $stdin = null, array $env = null): FlashMessageBag
    {
        try {
            $this->shell->exec($cmd, $args, $stdin, $env);
        } catch (ShellExecException $e) {
            return (new FlashMessageBag())->addMessage('error', $e->getMessage());
        }

        $messages = new FlashMessageBag();
        foreach ($this->shell->getError() as $e) {
            $messages->addMessage('error', $e);
        }

        return $messages;
    }

    /**
     * Create bunch of NACs using NAC management form
     */
    public function createNacs(CreateNacs $createNacs): int
    {
        $this->nacWarnings = [];

        // NAC template - new NACs are cloned from this object
        $nacTemplate = new Nac();
        $nacTemplate->setRemain((string)($createNacs->getDuration() * 60));
        $nacTemplate->setOwner($createNacs->getCreator());

        $count = 0;

        switch ($createNacs->getAssignment()) {
            case CreateNacs::ASSIGNMENT_TYPE_FREE_USAGE:
                // Create one or more unassigned NACs
                $count = $createNacs->getCount();
                for ($i = 0; $i < $count; $i++) {
                    $this->insertNac(clone $nacTemplate);
                }

                break;
            case CreateNacs::ASSIGNMENT_TYPE_USER:
                // Create PAC for a single user
                $nac = clone $nacTemplate;
                $user = $createNacs->getUser();
                // User can only have one NAC at the same time
                if ($this->hasNac($user)) {
                    $this->nacWarnings[] = __('Not created a NAC for %s, because User %s has already a NAC.', (string)$user, (string)$user);
                    break;
                }
                if (null === $user) {
                    throw new \LogicException('User must be not null.');
                }

                $nac->setUser($user);
                // (Behave like IServ 2 and don't set assigned datetime)
                $this->insertNac($nac);
                $count = 1;

                break;

            case CreateNacs::ASSIGNMENT_TYPE_GROUP:
                // Create NAC for all users of a group
                /* @var $group Group */
                $group = $createNacs->getGroup();

                if (null === $group) {
                    throw new \LogicException('Group must be not null.');
                }

                foreach ($group->getUsers() as $user) {
                    $nac = clone $nacTemplate;
                    // User can only have one NAC at the same time
                    if ($this->hasNac($user)) {
                        $this->nacWarnings[] = __('Not created a NAC for %s, because User %s has already a NAC.', (string)$user, (string)$user);
                        continue;
                    }
                    $nac->setUser($user);
                    // (Behave like IServ 2 and don't set assigned datetime)
                    $this->insertNac($nac);
                    $count++;
                }

                break;

            case CreateNacs::ASSIGNMENT_TYPE_ALL:
                // Create NAC for all users
                $users = $this->em->getRepository('IServCoreBundle:User')->findAll();
                foreach ($users as $user) {
                    // User can only have one NAC at the same time
                    if ($this->hasNac($user)) {
                        $this->nacWarnings[] = __('Not created a NAC for %s, because User %s has already a NAC.', (string)$user, (string)$user);
                        continue;
                    }
                    $nac = clone($nacTemplate);
                    $nac->setUser($user);
                    // (Behave like IServ 2 and don't set assigned datetime)
                    $this->insertNac($nac);
                    $count++;
                }

                break;

            default:
                // Invalid/Unknown value for assignment field
                return 0;

        }

        // Log
        $value = $nacTemplate->getNac();
        $msg = sprintf('%d %s mit %s Minuten hinzugefügt', $count, $count === 1 ? 'NAC' : 'NACs', $value);
        $this->logger->write($msg, null, 'Internet');

        return $count;
    }

    /**
     * Store a new NAC in database
     *
     * Not using the entity manager here to be able to catch
     * UniqueConstraintViolationException and retry the
     * insert with another random chosen NAC.
     */
    public function insertNac(Nac $nac): void
    {
        $expire = Zeit::now()->add(new \DateInterval('P1M'));

        $nacData = [
            'act' => $nac->getUser() === null ? null : $nac->getUser()->getUsername(),
            'owner' => $nac->getOwner() === null ? null : $nac->getOwner()->getUsername(),
            'remain' => $nac->getRemain(),
            'nac' => $nac->getNac(),
            'created' => $nac->getCreated()->format('c'),
            'expire' => $expire->format('c'), // expire after one month
            'assigned' => $nac->getAssigned(),
        ];

        // Maximum number of tries
        for ($i = 0; $i < 10000; $i++) {
            $nacData['nac'] = $this->generateRandomNac();
            try {
                $this->connection->insert('nacs', $nacData);

                return;
            } catch (UniqueConstraintViolationException $e) {
                // (retry with new NAC)
            } catch (Exception $e) {
                throw  new \RuntimeException('Could not insert NAC.', 0, $e);
            }
        }

        throw new \RuntimeException("Unable to create NAC (no NACs left)");
    }

    /**
     * Generate new random NAC id
     */
    private function generateRandomNac(): string
    {
        try {
            return sprintf("%08d", random_int(0, 99999999));
        } catch (\Exception $e) {
            throw new \RuntimeException('Could not generate NAC.', 0, $e);
        }
    }

    /**
     * Get warnings thrown during NAC creation
     *
     * @return string[]
     */
    public function getNacWarnings(): array
    {
        return $this->nacWarnings;
    }

    /**
     * Unlock access to Internet with NAC.
     * If no NAC supplied, the auto detection of current NAC will tried.
     */
    public function grantInternet(?string $nac = null): FlashMessageBag
    {
        if ($nac !== null && !$this->hasNac()) {
            /* @var $nacData \Stsbl\InternetBundle\Entity\Nac */
            $nacData = $this->em->getRepository(Nac::class)->findOneByNac($nac);
        } else {
            $nacData = $this->getUserNac();
            $nac = $nacData->getNac();
        }

        if ($nacData->getAssigned() === null) {
            $nacData->setAssigned(\DateTime::createFromImmutable(Zeit::now()));
            $this->em->persist($nacData);
            $this->em->flush();
        }

        $rsm = new ResultSetMapping();
        /* @var $nq \Doctrine\ORM\NativeQuery */
        $nq = $this->em->createNativeQuery('UPDATE nacs SET Timer = now() + Remain, ' .
            'Act = :1, IP = :2 WHERE Nac = :3', $rsm);

        $nq
            ->setParameter(1, $this->getUser()->getUsername())
            ->setParameter(2, $this->request->getClientIp())
            ->setParameter(3, $nac)
            ->execute()
        ;

        return $this->inetTimer();
    }

    /**
     * Revoke access to Internet with NAC.
     * If no NAC supplied, the auto detection of current NAC will tried.
     */
    public function revokeInternet(string $ip): FlashMessageBag
    {
        $rsm = new ResultSetMapping();
        /* @var $nq \Doctrine\ORM\NativeQuery */
        $nq = $this->em->createNativeQuery('UPDATE nacs SET Remain = Timer - now(), ' .
            'Timer = null, IP = null WHERE Act = :1 AND IP = :2 AND Timer IS NOT NULL', $rsm);

        $nq
            ->setParameter(1, $this->getUser()->getUsername())
            ->setParameter(2, $ip)
            ->execute()
        ;

        return $this->inetTimer();
    }
}
