<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
//    Page qui permet de rediriger automatiquement l'utilisateur lors de sa première connexion
//      Et lui attribue une langue de base
    #[Route('/', name: 'app_index')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_produit_index');
    }
}
