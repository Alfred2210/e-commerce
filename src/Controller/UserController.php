<?php

namespace App\Controller;

use App\Entity\Panier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user')]
    public function index(EntityManagerInterface $em): Response
    {
        return $this->render('user/index.html.twig', [
            'user' => $this->getUser(),
            'paniers' => $em->getRepository(Panier::class)->findBy(['user' => $this->getUser()])
        ]);
    }
}
