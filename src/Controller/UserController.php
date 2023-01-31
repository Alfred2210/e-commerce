<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Form\EditUserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;



#[Route('{_locale}')]
class UserController extends AbstractController
{
//    Page User
    #[Route('/user', name: 'app_user')]
    public function index(EntityManagerInterface $em): Response
    {
//        On récupère l'utilisateur actuel pour créer un form
//          afin qu'il puisse changer ses information directement
        $user = $this->getUser();
        $form = $this->createForm(EditUserType::class, $user);

//        On récupère tout les paniers de l'utilisateur et on defini le prix de chaque panier
        $panier = $em->getRepository(Panier::class)->findBy(['user' => $this->getUser()]);
        foreach ($panier as $unit){
            $unit->setPrix();
        }
        return $this->render('user/index.html.twig', [
            'form' => $form,
            'user' => $this->getUser(),
            'paniers' => $panier,
        ]);
    }

    #[Route('/moderator', name: 'app_moderator')]
    public function test(UserRepository $user): Response
    {
//        On affiche tous les utilisateurs
        return $this->render('user/last.html.twig', [
            'user' => $user->getUsers()
        ]);
    }
}
