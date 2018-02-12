<?php declare(strict_types=1);

namespace Shopware\Storefront\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * @Route(service="Shopware\Storefront\Controller\AccountController")
 */
class AccountController extends StorefrontController
{
    /**
     * @var AuthenticationUtils
     */
    private $authUtils;

    public function __construct(AuthenticationUtils $authUtils)
    {
        $this->authUtils = $authUtils;
    }

    /**
     * @Route("/account", name="account_home")
     */
    public function indexAction()
    {
        return $this->renderStorefront('frontend/account/index.html.twig');
    }

    /**
     * @Route("/account/login", name="account_login", methods={"GET"})
     */
    public function loginAction()
    {
        // get the login error if there is one
        $error = $this->authUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastEmail = $this->authUtils->getLastUsername();

        return $this->renderStorefront('frontend/register/index.html.twig', [
            'last_email' => $lastEmail,
            'error' => $error,
        ]);
    }

    /**
     * @Route("/account/login", name="account_login_check", methods={"POST"})
     */
    public function checkLoginAction()
    {
    }

    /**
     * @Route("/account/logout", name="account_logout")
     */
    public function logoutAction()
    {
        return new Response('<html><body>Admin page!</body></html>');
    }
}
