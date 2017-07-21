<?php
// src/Stsbl/InternetBundle/Service/NacManager.php
namespace Stsbl\InternetBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\ResultSetMapping;
use IServ\CoreBundle\Entity\User;
use IServ\CoreBundle\Security\Core\SecurityHandler;
use IServ\CoreBundle\Service\Logger;
use IServ\CoreBundle\Service\Shell;
use IServ\CrudBundle\Entity\FlashMessageBag;
use Stsbl\InternetBundle\Entity\Nac;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

/*
 * The MIT License
 *
 * Copyright 2017 Felix Jacobi.
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
class NacManager 
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var SecurityHandler
     */
    private $securityHandler;

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
     * @var array
     */
    private $nacWarnings = [];

    /**
     * The constructor
     *
     * @param SecurityHandler $securityHandler
     * @param RequestStack $requestStack
     * @param Shell $shell
     * @param Doctrine $doctrine
     * @param Logger $logger
     */
    public function __construct(SecurityHandler $securityHandler, RequestStack $requestStack, Shell $shell, Doctrine $doctrine, Logger $logger)
    {
        $this->em = $doctrine->getManager();
        $this->securityHandler = $securityHandler;
        $this->request = $requestStack->getCurrentRequest();
        $this->shell = $shell;
        $this->connection = $doctrine->getConnection();
        $this->logger = $logger;
    }

    /**
     * Checks if user have a NAC.
     * If no user given, assume the current one.
     * 
     * @param UserInterface $user
     * @return boolean
     */
    public function hasNac(UserInterface $user = null)
    {
        /* @var $nacRepository \Stsbl\InternetBundle\Entity\NacRepository */
        $nacRepository = $this->em->getRepository('StsblInternetBundle:Nac');
        return $nacRepository->hasNac($this->getUser($user));
    }

    /**
     * Get NAC for user.
     * If no user given, assume the current one.
     * Note, that will throw an \RuntimeException, 
     * if no NAC exists.
     * 
     * @param UserInterface $user
     * @return \Stsbl\InternetBundle\Entity\Nac
     */
    public function getUserNac(UserInterface $user = null)
    {
        /* @var $nacRepository \Stsbl\InternetBundle\Entity\NacRepository */
        $nacRepository = $this->em->getRepository('StsblInternetBundle:Nac');
        return $nacRepository->findNacByUser($this->getUser($user));
    }

    /**
     * Shortcut to determine user (return user from security handler, if user is null).
     * 
     * @param UserInterface $user
     * @return UserInterface
     */
    private function getUser(UserInterface $user = null)
    {
        if ($user === null) {
            $user = $this->securityHandler->getUser();
        }

        return $user;
    }

    /**
     * Check if internet access is already granted to the current client.
     * 
     * @return boolean 
     */
    public function isInternetGranted()
    {
        // ignore if host is not in host management
        if ($this->em->getRepository('IServHostBundle:Host')->findOneByIp($this->request->getClientIp()) === null) {
            return false;
        }

        try {
            return $this->em->getRepository('IServHostBundle:Host')->findOneBy(['internet' => true, 'overrideRoute' => null, 'ip' => $this->request->getClientIp()]) != null;
        } catch (NoResultException $e) {
            try {
                return $this->em->getRepository('IServHostBundle:Host')->findOneBy(['overrideRoute' => true, 'ip' => $this->request->getClientIp()]) != null;
            } catch (NoResultException $e) {
                return false;
            }
        }
    }

    /**
     * Get internet information (overrideUntil and overrideBy).
     * 
     * @return array
     */
    public function getInternetInformation()
    {
        try {
            /* @var $host \IServ\HostBundle\Entity\Host */
            $host = $this->em->getRepository('IServHostBundle:Host')->findOneByIp($this->request->getClientIp());
        } catch (NoResultException $e) {
            return [
                'until' => null,
                'by' => null,
            ];
        }

        if ($host === null) {
            return [
                'until' => null,
                'by' => null,
            ];
        }

        return [
            'until' => $host->getOverrideUntil(),
            'by' => $this->em->getRepository('IServCoreBundle:User')->findOneByUsername($host->getOverrideBy()),
        ];
    }

    /**
     * Check if internet access is explicitly denied for the current client.
     * 
     * @return boolean 
     */
    public function isInternetDenied()
    {
        try {
            return $this->em->getRepository('IServHostBundle:Host')->findOneBy(['overrideRoute' => false, 'ip' => $this->request->getClientIp()]) != null;
        } catch (NoResultException $e) {
            return false;
        }
    }

    /**
     * Runs inet_timer.
     * 
     * @return FlashMessageBag
     */
    public function inetTimer()
    {
        system("killall -q -SIGHUP -r '^inet_timer:' || ".
            "killall -q -SIGHUP 'inet_timer'", $err);

        if ($err) {
            return $this->shellMsgError('closefd', ['/usr/lib/iserv/inet_timer', '-d']);
        } else {
            return new FlashMessageBag();
        }
    }

    /**
     * Execute a command and return a FlashMessageBag with STDERR 
     * lines as error messages.
     * Similar to the original from HostManager, but only show
     * STDERR lines.
     *
     * @param string $cmd
     * @param mixed $args
     * @param mixed $stdin
     * @param array $env
     * @return FlashMessageBag STDERR content as FlashMessageBag
     */
    private function shellMsgError($cmd, $args = null, $stdin = null, $env = null)
    {
        $this->shell->exec($cmd, $args, $stdin, $env);

        $messages = new FlashMessageBag();
        foreach ($this->shell->getError() as $e) {
            $messages->addMessage('error', $e);
        }

        return $messages;
    }

    /**
     * Create bunch of NACs using NAC management form
     *
     * @param Form $addForm
     * @param User $creator
     * @return integer
     */
    public function createNacsByForm(Form $addForm, User $creator)
    {
        $this->nacWarnings = [];

        // NAC template - new NACs are cloned from this object
        $nacTpl = new Nac();
        $nacTpl->setNac($addForm['value']->getData());
        $nacTpl->setRemain((integer)$addForm['value']->getData() * 60);
        $nacTpl->setOwner($creator);

        $count = 0;

        switch ($addForm['assignment']->getData()) {

            case 'free_usage':
                // Create one or more unassigned NACs
                $count = $addForm['count']->getData();
                for ($i = 0; $i < $count; $i++) {
                    $this->insertNac(clone($nacTpl));
                }

                break;

            case 'user':
                // Create PAC for a single user
                $nac = clone($nacTpl);
                $user = $addForm['user']->getData();
                // User can only have one NAC at the same time
                if ($this->hasNac($user)) {
                    $this->nacWarnings[] = __('Not created a NAC for %s, because User %s has already a NAC.', (string)$user, (string)$user);
                    break;
                }
                $nac->setUser($user);
                // (Behave like IServ 2 and don't set assigned datetime)
                $this->insertNac($nac);
                $count = 1;

                break;

            case 'group':
                // Create NAC for all users of a group
                /* @var $group \IServ\CoreBundle\Entity\Group */
                $group = $addForm['group']->getData();
                foreach ($group->getUsers() as $user) {
                    $nac = clone($nacTpl);
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

            case 'all':
                // Create NAC for all users
                $users = $this->em->getRepository('IServCoreBundle:User')->findAll();
                foreach ($users as $user) {
                    // User can only have one NAC at the same time
                    if ($this->hasNac($user)) {
                        $this->nacWarnings[] = __('Not created a NAC for %s, because User %s has already a NAC.', (string)$user, (string)$user);
                        continue;
                    }
                    $nac = clone($nacTpl);
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
        $value = $nacTpl->getNac();
        $msg = sprintf('%d %s mit "%s" hinzugefügt', $count, $count === 1 ? 'NAC' : 'NACs', $value);
        $this->logger->write($msg, null, 'Internet');

        return $count;
    }

    /**
     * Store a new NAC in database
     *
     * Not using the entity manager here to be able to catch
     * UniqueConstraintViolationException and retry the
     * insert with another random chosen NAC.
     *
     * @param Nac $nac
     * @throws \Exception
     */
    public function insertNac(Nac $nac)
    {
        $expire = date('c', time() + 60 * 60 * 24 * 30);

        $nacData = array(
            'act' => $nac->getUser() === null ? null : $nac->getUser()->getUsername(),
            'owner' => $nac->getOwner() === null ? null : $nac->getOwner()->getUsername(),
            'remain' => $nac->getRemain(),
            'nac' => $nac->getNac(),
            'created' => $nac->getCreated()->format('c'),
            'expire' => $expire, // expire after one month
            'assigned' => $nac->getAssigned() === null ? null : $nac->getAssigned()->getUsername(),
        );

        // Maximum number of tries
        for ($i = 0; $i < 10000; $i++) {
            try {
                $nacData['nac'] = $this->generateRandomNac();
                $this->connection->insert('nacs', $nacData);

                return;
            }
            catch (UniqueConstraintViolationException $e) {
                // (retry with new NAC)
            }
        }

        throw new \Exception("Unable to create NAC (no NACs left)");
    }

    /**
     * Generate new random NAC id
     *
     * @return string
     */
    private function generateRandomNac()
    {
        return sprintf("%08d", rand(0, 99999999));
    }

    /**
     * Get warnings thrown during NAC creation
     * 
     * @return array
     */
    public function getNacWarnings()
    {
        return $this->nacWarnings;
    }

    /**
     * Unlock access to Internet with NAC.
     * If no NAC supplied, the auto detection of current NAC will tried.
     * 
     * @param string $nac
     * @return FlashMessageBag
     */
    public function grantInternet($nac = null)
    {
        if ($nac != null && !$this->hasNac()) {
            /* @var $nacData \Stsbl\InternetBundle\Entity\Nac */
            $nacData = $this->getDoctrine()->getRepository('StsblInternetBundle:Nac')->findOneByNac($nac);  
        } else {
            $nacData = $this->getUserNac();
            $nac = $nacData->getNac();
        }

        if ($nacData->getAssigned() === null) {
            $nacData->setAssigned(new \DateTime('now'));
            $this->em->persist($nacData);
            $this->em->flush();
        }

        $rsm = new ResultSetMapping();
        /* @var $nq \Doctrine\ORM\NativeQuery */
        $nq = $this->em->createNativeQuery('UPDATE nacs SET Timer = now() + Remain, '.
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
     * 
     * @param string $ip
     * @return FlashMessageBag
     */
    public function revokeInternet($ip)
    {
        $rsm = new ResultSetMapping();
        /* @var $nq \Doctrine\ORM\NativeQuery */
        $nq = $this->em->createNativeQuery('UPDATE nacs SET Remain = Timer - now(), '.
            'Timer = null, IP = null WHERE Act = :1 AND IP = :2 AND Timer IS NOT NULL', $rsm);

        $nq
            ->setParameter(1, $this->getUser()->getUsername())
            ->setParameter(2, $ip)
            ->execute()
        ;

        return $this->inetTimer();
    }
}
