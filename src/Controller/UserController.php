<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;



#[Route('{_locale}')]
class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user')]
    public function index(EntityManagerInterface $em, UserRepository $user): Response
    {

        $user->getUsers();
        return $this->render('user/index.html.twig', [
            'user' => $this->getUser(),
            'paniers' => $em->getRepository(Panier::class)->findBy(['user' => $this->getUser()])
        ]);
    }

    #[Route('/moderator', name: 'app_moderator')]
    public function test(UserRepository $user): Response
    {
        return $this->render('user/last.html.twig', [
            'user' => $user->getUsers()
        ]);
    }
}
