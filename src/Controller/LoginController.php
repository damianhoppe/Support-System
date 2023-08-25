<?php

namespace App\Controller;

use App\Entity\Category;
use Psr\Log\LoggerInterface;
use App\Repository\CategoryRepository;
use App\Repository\QuestionRepository;
use Drenso\OidcBundle\OidcClientInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Drenso\OidcBundle\Exception\OidcException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LoginController extends AbstractController {

    /**
     * This controller forwards the user to the OIDC login
     *
     * @throws OidcException
     */
    #[Route('/login_oidc', name: 'login_oidc')]
    public function surfconext(OidcClientInterface $oidcClient, LoggerInterface $logger): Response {
        return $oidcClient->generateAuthorizationRedirect();
    }
    
    #[Route('/login', name: 'app_login')]
    public function login(): Response {
        return new RedirectResponse('/login_oidc');
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(Security $security): Response
    {
        $security->logout(false);
        return new RedirectResponse('/');
    }
    
    #[Route('/login_check', name: 'login_check')]
    #[IsGranted("IS_AUTHENTICATED_ANONYMOUSLY")]
    public function checkLogin(): RedirectResponse
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirect('/');
        } else {
            return $this->redirect($this->generateUrl('login'));
        }
    }
}