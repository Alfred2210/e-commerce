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
    #[Route('/user', name: 'app_user')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(EditUserType::class, $user);

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
        return $this->render('user/last.html.twig', [
            'user' => $user->getUsers()
        ]);
    }
}
