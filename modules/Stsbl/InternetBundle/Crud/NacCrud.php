<?php

declare(strict_types=1);

namespace Stsbl\InternetBundle\Crud;

use IServ\CoreBundle\Entity\Specification\PropertyMatchSpecification;
use IServ\CoreBundle\Service\Logger;
use IServ\CoreBundle\Traits\LoggerTrait;
use IServ\CrudBundle\Crud\ServiceCrud;
use IServ\CrudBundle\Doctrine\Specification\SpecificationInterface;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Mapper\ListMapper;
use IServ\CrudBundle\Routing\RoutingDefinition;
use IServ\CrudBundle\Table\Specification\FilterExpression;
use IServ\Library\Config\Config;
use Stsbl\InternetBundle\Controller\NacController;
use Stsbl\InternetBundle\Entity\Nac;
use Stsbl\InternetBundle\Security\Privilege;
use Stsbl\InternetBundle\Service\NacManager;
use Stsbl\InternetBundle\Twig\Extension\Time;
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
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
final class NacCrud extends ServiceCrud
{
    use LoggerTrait;

    /**
     * {@inheritDoc}
     */
    protected static $entityClass = Nac::class;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->title = _('Manage NACs');
        $this->itemTitle = 'NAC';
        $this->templates['crud_index'] = '@StsblInternet/Nac/index.html.twig';

        $this->logModule = 'Internet';
    }

    /**
     * {@inheritdoc}
     */
    public function prepareBreadcrumbs(): array
    {
        return [_('Internet') => $this->router()->generate('internet_index')];
    }

    /**
     * {@inheritDoc}
     */
    public static function defineRoutes(): RoutingDefinition
    {
        return parent::defineRoutes()
            ->useControllerForAction(self::ACTION_INDEX, NacController::class . '::indexAction')
            ->setNamePrefix('internet_manage_')
            ->setPathPrefix('/internet/manage/')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function postRemove(CrudInterface $object): void
    {
        // Log deletion of NACs
        /* @var Nac $nac */
        $value = $this->time()->intervalToString($nac->getRemain());
        if ($nac->getUser() === null) {
            $msg = sprintf('NAC "%s" mit %s verbleibender Zeit erstellt von "%s" gelöscht', $nac->getId(), $value, $nac->getOwner()->getName());
        } else {
            $msg = sprintf('NAC "%s" mit %s verbleibender Zeit erstellt von "%s" und vergeben an "%s" gelöscht', $nac->getId(), $value, $nac->getOwner()->getName(), $nac->getUser()->getName());
        }
        $this->log($msg);
        // run inet_timer to disable deleted NACs
        $this->nacManager()->inetTimer();
    }

    /**
     * {@inheritdoc}
     */
    public function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->add('nac', null, ['label' => _('NAC'), 'responsive' => 'all'])
            ->add('owner', 'entity', ['label' => _('Created by'), 'responsive' => 'desktop'])
            ->add('created', 'datetime', ['label' => _('Created on'), 'responsive' => 'desktop'])
            ->add('remain', null, [
                'template' => '@StsblInternet/List/field_interval.html.twig',
                'label' => _('Remaining'),
                'responsive' => 'min-mobile',
            ])
            ->add('user', 'entity', ['label' => _('Assigned to'), 'responsive' => 'all'])
            ->add('assigned', 'datetime', ['label' => _('Assigned on'), 'responsive' => 'desktop'])
            ->add('ip', null, [
                'template' => '@StsblInternet/List/field_status.html.twig',
                'label' => _('Status'),
                'responsive' => 'desktop'])
        ;
    }


    /**
     * {@inheritDoc}
     */
    public function isAllowedTo(string $action, UserInterface $user, CrudInterface $object = null): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isAuthorized(): bool
    {
        return $this->authorizationChecker()->isGranted(Privilege::INET_NACS) && $this->config()->get('Activation');
    }

    /**
     * {@inheritDoc}
     */
    public function getFilterSpecification(): ?SpecificationInterface
    {
        // no filtering for admins
        $user = $this->getUser();

        if ($user !== null && $user->isAdmin()) {
            return null;
        }

        // No user => failsafe
        if (null === $user) {
            return new FilterExpression('1 = 2');
        }
        return new PropertyMatchSpecification('owner', $user->getUsername());
    }

    private function config(): Config
    {
        return $this->locator->get(Config::class);
    }

    private function nacManager(): NacManager
    {
        return $this->locator->get(NacManager::class);
    }

    private function time(): Time
    {
        return $this->locator->get(Time::class);
    }

    /**
     * @required
     */
    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
    {
        $deps = parent::getSubscribedServices();
        $deps[] = Config::class;
        $deps[] = NacManager::class;
        $deps[] = Time::class;

        return $deps;
    }
}
