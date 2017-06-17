<?php
namespace Stsbl\InternetBundle\Controller;

use IServ\CrudBundle\Controller\CrudController;
use Stsbl\InternetBundle\Form\Type\NacCreateType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

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
 * Customized CrudController for PAC listing.
 *
 * Adds add-form for NAC to index template and print action.
 * 
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class NacController extends CrudController
{
    /**
     * {@inheritdoc}
     */
    public function indexAction(Request $request)
    {
        // NAC add form
        $addForm = $this->createForm(NacCreateType::class, null, ['default_credits' => 45]);
        $addForm->handleRequest($request);
        if ($addForm->isSubmitted() && $addForm->isValid()) {
            /* @var $nacManager \Stsbl\InternetBundle\Service\NacManager */
            $nacManager = $this->get('stsbl.internet.nac_manager');
            $created = $nacManager->createNacsByForm($addForm, $this->getUser());

            $warnings = $nacManager->getNacWarnings();
            if (count($warnings) > 0) {
                $this->get('iserv.flash')->alert(join("\n", $warnings));
            }

            if ($created > 0) {
                $this->get('iserv.flash')->success(_n('The NAC has been created.', 'The NACs have been created.', $created));

                return $this->redirect($this->generateUrl('internet_manage_nac_index'));
            }

        }

        $response = parent::indexAction($request);
        $response['addForm'] = $addForm->createView();

        return $response;
    }

    /**
     * Print list of unassigned NACs
     *
     * @Route("/internet/manage/nacs/print", name="internet_manage_nacs_print")
     * @Security("is_granted('PRIV_INET_NACS')")
     * @Template()
     */
    public function printAction()
    {
        if (!$this->get('iserv.config')->get('Activation')) {
            throw $this->createAccessDeniedException('The internet module is not available, if activation is disabled.');
        }

        $nacs = $this->getDoctrine()->getRepository('StsblInternetBundle:Nac')->findBy(array(
            'user' => null,
            'owner' => $this->getUser(),
        ));

        return array(
            'nacs' => $nacs,
            'currentUser' => $this->getUser(),
        );
    }

}
