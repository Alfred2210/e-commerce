<?php

namespace App\Controller;

use App\Entity\ContenuPanier;
use App\Entity\Panier;
use App\Form\PanierType;
use App\Repository\PanierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('{_locale}/panier')]
class PanierController extends AbstractController
{
    #[Route('/', name: 'app_panier_index', methods: ['GET'])]
    public function index(PanierRepository $panierRepository, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        if (in_array('ROLE_MODERATOR', $user->getRoles())) {

            return $this->render('panier/index.html.twig', [
                'paniers' => $panierRepository->findBy(['etat' => false]),
            ]);
        } else {
            $panier = $panierRepository->findOneBy(['user' => $user, 'etat' => false]);
            if (!$panier) {
                $panier = new Panier();
                $panier->setUser($this->getUser())
                    ->setEtat(false)
                    ->setDate(new \DateTime());
                $em->persist($panier);
                $em->flush();
            }
            return $this->render('panier/show.html.twig', [
                'panier' => $panier,
            ]);
        }
    }

    #[Route('/new', name: 'app_panier_new', methods: ['GET', 'POST'])]
    public function new(Request $request, PanierRepository $panierRepository): Response
    {
        $panier = new Panier();
        $form = $this->createForm(PanierType::class, $panier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $panierRepository->save($panier, true);

            return $this->redirectToRoute('app_panier_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('panier/new.html.twig', [
            'panier' => $panier,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_panier_show', methods: ['GET'])]
    public function show(Panier $panier, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if ($user === $panier->getUser() || in_array('ROLE_MODERATOR', $user->getRoles())) {
            return $this->render('panier/show.html.twig', [
                'panier' => $panier,
            ]);
        } else {
            $this->addFlash('warning', $translator->trans('flash.cant'));
            return $this->redirectToRoute('app_produit_index');
        }
    }

    #[Route('{id}/edit', name: 'app_panier_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Panier $panier, PanierRepository $panierRepository): Response
    {
        $form = $this->createForm(PanierType::class, $panier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $panierRepository->save($panier, true);

            return $this->redirectToRoute('app_panier_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('panier/edit.html.twig', [
            'panier' => $panier,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_panier_delete', methods: ['POST'])]
    public function delete(Request $request, Panier $panier, PanierRepository $panierRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $panier->getId(), $request->request->get('_token'))) {
            $panierRepository->remove($panier, true);
        }

        return $this->redirectToRoute('app_panier_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('line/{id}', name: 'app_panier_remove_line')]
    public function removeLigne(ContenuPanier $contenu, EntityManagerInterface $em, Request $r, TranslatorInterface $translator)
    {
        if ($contenu->getPanier()->getUser() !== $this->getUser()) {
            $this->addFlash('warning', $translator->trans('flash.cant'));
            return $this->redirectToRoute('app_panier_show', ['id' => $contenu->getPanier()->getId()]);
        } else {
            $em->remove($contenu);
            $em->flush();
            $this->addFlash('warning', $translator->trans('flash.remove_prod'));

            return $this->redirectToRoute('app_panier_index');
        }
    }

    #[Route('valid/{id}', name: 'app_panier_valid')]
    public function validPanier(Panier $panner, EntityManagerInterface $em, Request $r, TranslatorInterface $translator)
    {
        if ($panner->getUser() !== $this->getUser()) {
            $this->addFlash('warning', $translator->trans('flash.cant'));
            return $this->redirectToRoute('app_panier_show', ['id' => $panner->getId()]);
        } else {
            $panner->setEtat(true);
            $panner->setDate(new \DateTime());
            $em->persist($panner);
            $em->flush();
            $this->addFlash('success', $translator->trans('flash.buy'));

            return $this->redirectToRoute('app_produit_index');
        }
    }
}
