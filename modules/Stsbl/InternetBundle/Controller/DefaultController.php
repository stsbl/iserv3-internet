<?php

declare(strict_types=1);

namespace Stsbl\InternetBundle\Controller;

use IServ\CoreBundle\Controller\AbstractPageController;
use IServ\HostBundle\Service\Network;
use IServ\Library\Config\Config;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Stsbl\InternetBundle\Service\NacManager;
use Stsbl\InternetBundle\Validator\Constraints\Nac;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;

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
final class DefaultController extends AbstractPageController
{
    private function getNacForm(): FormInterface
    {
        $builder = $this->formFactory()->createNamedBuilder('nac');

        if (!$this->nacManager()->hasNac()) {
            $builder
                ->add('nac', TextType::class, [
                    'label' => false,
                    'constraints' => [new NotBlank(), new Nac()],
                    'attr' => [
                        'required' => 'required',
                        'placeholder' => _('NAC'),
                    ],
                ])
            ;
        }

        if (!$this->nacManager()->hasNac() || ($this->nacManager()->hasNac() && $this->nacManager()->getUserNac()->getTimer() == null)) {
            $builder
                ->add('grant', SubmitType::class, [
                    'label' => _('Grant access'),
                    'buttonClass' => 'btn-success',
                    'icon' => 'pro-unlock',
                ])
            ;
        } else {
            $builder
                ->add('revoke', SubmitType::class, [
                    'label' => _('Revoke'),
                    'buttonClass' => 'btn-danger',
                    'icon' => 'off'
                ])
            ;
        }

        return $builder->getForm();
    }

    /**
     * @Route("/internet", name="internet_index")
     * @Template()
     */
    public function index(Config $config, Request $request): RedirectResponse|array
    {
        if (!$config->get('Activation')) {
            throw $this->createAccessDeniedException('The internet module is not available, if activation is disabled.');
        }

        $form = $this->getNacForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nac = $form->getData()['nac'] ?? null;

            if ($form->has('grant') && $form->get('grant')->isClicked()) {
                $this->nacManager()->grantInternet($nac);
                $this->addFlash('success', _('Internet access with NAC successful granted.'));
            } elseif ($form->has('revoke') && $form->get('revoke')->isClicked()) {
                $this->nacManager()->revokeInternet($request->getClientIp());
                $this->addFlash('success', _('Internet access with NAC successful revoked.'));
            }

            return $this->redirect($this->generateUrl('internet_index'));
        }

        // track path
        $this->addBreadcrumb(_('Internet'), $this->generateUrl('internet_index'));

        return [
            'form' => $form->createView(),
            'controller' => $this,
        ];
    }

    public function nacManager(): NacManager
    {
        return $this->container->get(NacManager::class);
    }

    public function network(): Network
    {
        return $this->container->get(Network::class);
    }

    private function formFactory(): FormFactoryInterface
    {
        return $this->get('form.factory');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        $deps = parent::getSubscribedServices();
        $deps[] = NacManager::class;
        $deps[] = Network::class;

        return $deps;
    }
}
