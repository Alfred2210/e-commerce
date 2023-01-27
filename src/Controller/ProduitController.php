<?php

namespace App\Controller;

use App\Entity\ContenuPanier;
use App\Entity\Panier;
use App\Entity\Produit;
use App\Form\ContenueType;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Tests\Common\Annotations\Fixtures\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/produit')]
class ProduitController extends AbstractController
{
    #[Route('/', name: 'app_produit_index', methods: ['GET'])]
    public function index(ProduitRepository $produitRepository): Response
    {
        return $this->render('produit/index.html.twig', [
            'produits' => $produitRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_produit_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ProduitRepository $produitRepository): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $logo = $form->get('photo')->getData();
            if ($logo) {
                $newFilename = uniqid() . '.' . $logo->guessExtension();

                try {
                    $logo->move(
                        $this->getParameter('upload_dir'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('danger', $e->getMessage());
                    return $this->redirectToRoute('app_marque');
                }

                $produit->setPhoto($newFilename);
            }

            $produitRepository->save($produit, true);
            return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('produit/new.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_produit_show')]
    public function show(Produit $produit, EntityManagerInterface $em, Request $r): Response
    {
        $user = $this->getUser();
        if ($user) {

            $panner = $em->getRepository(Panier::class)->findOneBy(['user' => $user, 'etat' => false]);
            if (!$panner) {
                $panner = new Panier();
                $panner->setUser($user);
                $panner->setDate(new \DateTime());
                $panner->setEtat(false);
                $em->persist($panner);
                $em->flush();
            }
            $list = $em->getRepository(ContenuPanier::class)->findOneBy(['panier' => $panner, 'produit' => $produit]);
            if (!$list) {
                $list = new ContenuPanier();
                $list->setDate(new \DateTime())
                    ->setPanier($panner)
                    ->setProduit($produit);
            }
            $form = $this->createForm(ContenueType::class, $list);
            $form->handleRequest($r);
            if ($form->isSubmitted() && $form->isValid()) {
                $em->persist($list);
                $em->flush();
            }

            return $this->render('produit/show.html.twig', [
                'produit' => $produit,
                'form' => $form->createView(),
            ]);
        }
        return $this->render('produit/show.html.twig', [
            'produit' => $produit,
        ]);

    }

    #[Route('/{id}/edit', name: 'app_produit_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Produit $produit, ProduitRepository $produitRepository): Response
    {
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $produitRepository->save($produit, true);

            return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_produit_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit, ProduitRepository $produitRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $produit->getId(), $request->request->get('_token'))) {
            $produitRepository->remove($produit, true);
        }

        return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/{qt}/topanner', name: 'app_produit_to_panner')]
    public function addProduct(Produit $prod, EntityManagerInterface $em, Request $r)
    {
        $user = $this->getUser();
        $panner = $em->getRepository(Panier::class)->findOneBy(['user' => $user, 'etat' => false]);
        if (!$panner) {
            $panner = new Panier();
            $panner->setUser($user);
            $panner->setDate(new \DateTime());
            $panner->setEtat(false);
            $em->persist($panner);
            $em->flush();
        }


        return $this->render('dump.html.twig', [
            'test' => $r->query->get('gt'),
        ]);
    }
}
