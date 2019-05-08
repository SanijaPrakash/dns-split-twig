<?php
/**
 * Created by: Sanija K
 * User : vinam
 * Date : 4-04-2019 11:10 AM
 */
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class MainDnsController extends AbstractController
{
    public $userId = 0;
    public $userName = '';

    /**
     * @param $session SessionInterface
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @return void
     */
    public function setPublicValues(): void
    {
        $user = $this->getUser();
        if(!empty($user)){
            $this->userId = $user->getId();
            $this->userName =  $user->getName();
        }
    }
}
