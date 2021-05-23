<?php

declare(strict_types=1);

namespace Stsbl\InternetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/internet/manage/nacs", name="internet_manage_nac_legacy_redirect")
 */
final class RedirectController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->redirectToRoute('internet_manage_nac_index', [], Response::HTTP_PERMANENTLY_REDIRECT);
    }
}
