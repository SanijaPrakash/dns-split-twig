<?php
/**
 * Created by: Sanija K
 * User : vinam
 * Date : 2-04-2019 10:00 AM
 */
namespace App\Controller;

use App\Controller\MainDnsController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UserLoginController extends MainDnsController
{
    public function __construct(SessionInterface $session)
    {
        parent::__construct($session);
    }

    /**
     * @Route("login",name="login")
     * @param AuthenticationUtils $utils
     * @return Response
     */
    public function login(AuthenticationUtils $utils): Response
    {
        $user = $this->getUser();
        if (empty($user)) {
            $lastUsername = $utils->getLastUsername();
            $error = $utils->getLastAuthenticationError();
            return $this->render('login.html.twig', [
                'lastUsername' => $lastUsername,
                'error' => $error
            ]);
        } else {
            return new RedirectResponse('home');
        } 
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout()
    {

    }
}
