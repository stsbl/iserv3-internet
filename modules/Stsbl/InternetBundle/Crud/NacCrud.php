<?php
// src/Stsbl/InternetBundle/Crud/NacCrud.php
namespace Stsbl\InternetBundle\Crud;

use IServ\CoreBundle\Entity\Specification\PropertyMatchSpecification;
use IServ\CoreBundle\Service\Config;
use IServ\CoreBundle\Service\Logger;
use IServ\CoreBundle\Traits\LoggerTrait;
use IServ\CrudBundle\Crud\AbstractCrud;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Mapper\ListMapper;
use Stsbl\InternetBundle\Controller\NacController;
use Stsbl\InternetBundle\Entity\Nac;
use Stsbl\InternetBundle\Security\Privilege;
use Stsbl\InternetBundle\Service\NacManager;
use Stsbl\InternetBundle\Twig\Extension\Time;
use Symfony\Component\Security\Core\User\UserInterface;

/*
 * The MIT License
 *
 * Copyright 2018 Felix Jacobi.
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
class NacCrud extends AbstractCrud
{
    use LoggerTrait;

    /**
     * @var Time
     */
    private $time;

    /**
     * @var NacManager
     */
    private $manager;

    /**
     * @var Config
     */
    private $config;

    /**
     * The constructor.
     */
    public function __construct()
    {
        parent::__construct(Nac::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->title = _('Manage NACs');
        $this->itemTitle = 'NAC';
        $this->routesPrefix = 'internet/manage/';
        $this->routesNamePrefix = 'internet_manage_';
        //$this->options['help'] = 'v3/modules/network/print/';
        $this->templates['crud_index'] = 'StsblInternetBundle:Nac:index.html.twig';

        $this->logModule = 'Internet';
    }

    /**
     * {@inheritdoc}
     */
    public function prepareBreadcrumbs()
    {
        return [_('Internet') => $this->router->generate('internet_index')];
    }

    /**
     * {@inheritdoc}
     */
    protected function buildRoutes()
    {
        parent::buildRoutes();

        $this->routes['index']['_controller'] = NacController::class . '::indexAction';
    }

    /**
     * {@inheritdoc}
     */
    public function postRemove(CrudInterface $nac)
    {
        // Log deletion of NACs
        /* @var $nac Nac */
        $value = $this->time->intervalToString($nac->getRemain());
        if ($nac->getUser() === null) {
            $msg = sprintf('NAC "%s" mit %s verbleibender Zeit erstellt von "%s" gelöscht', $nac->getId(), $value, $nac->getOwner()->getName());
        } else {
            $msg = sprintf('NAC "%s" mit %s verbleibender Zeit erstellt von "%s" und vergeben an "%s" gelöscht', $nac->getId(), $value, $nac->getOwner()->getName(), $nac->getUser()->getName());
        }
        $this->log($msg);
        // run inet_timer to disable deleted NACs
        $this->manager->inetTimer();
    }

    /**
     * {@inheritdoc}
     */
    public function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('nac', null, array('label' => _('NAC'), 'responsive' => 'all'))
            ->add('owner', 'entity', array('label' => _('Created by'), 'responsive' => 'desktop'))
            ->add('created', 'datetime', array('label' => _('Created on'), 'responsive' => 'desktop'))
            ->add('remain', null, array(
                'template' => 'StsblInternetBundle:List:field_interval.html.twig',
                'label' => _('Remaining'),
                'responsive' => 'min-mobile',
            ))
            ->add('user', 'entity', array('label' => _('Assigned to'), 'responsive' => 'all'))
            ->add('assigned', 'datetime', array('label' => _('Assigned on'), 'responsive' => 'desktop'))
            ->add('ip', null, array(
                'template' => 'StsblInternetBundle:List:field_status.html.twig',
                'label' => _('Status'),
                'responsive' => 'desktop'))
        ;
    }

    /*** SETTERS ***/

    /**
     * @param Time $time
     * @required
     */
    public function setTwigTimeExtension(Time $time)
    {
        $this->time = $time;
    }

    /**
     * @param NacManager $manager
     * @required
     */
    public function setManager(NacManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param Config $config
     * @required
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
    }

    /*** SECURITY ***/

    public function isAuthorized()
    {
        return $this->isGranted(Privilege::INET_NACS) && $this->config->get('Activation');
    }

    public function isAllowedToView(CrudInterface $object = null, UserInterface $user = null)
    {
        return false;
    }

    public function isAllowedToAdd(UserInterface $user = null)
    {
        return false;
    }

    public function isAllowedToEdit(CrudInterface $object = null, UserInterface $user = null)
    {
        return false;
    }

    public function getFilterSpecification()
    {
        // no filtering for admins
        if ($this->getUser()->isAdmin()) {
            return null;
        }

        return new PropertyMatchSpecification('owner', $this->getUser()->getUsername());
    }

    /**
     * @required
     */
    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }
}
