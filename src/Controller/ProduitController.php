<?php

namespace App\Controller;

use App\Entity\ContenuPanier;
use App\Entity\Panier;
use App\Entity\Produit;
use App\Form\ContenueType;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;


#[Route('{_locale}')]
class ProduitController extends AbstractController
{
    #[Route('/', name: 'app_produit_index', methods: ['GET'])]
    public function index(ProduitRepository $produitRepository): Response
    {
        return $this->render('produit/index.html.twig', [
            'produits' => $produitRepository->findAll(),
        ]);
    }

    #[Route('/produit/admin/new', name: 'app_produit_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ProduitRepository $produitRepository, TranslatorInterface $translator): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

//            Fonction de l'img
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
            $this->addFlash('success', $translator->trans('flash.new_prod'));
            return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('produit/new.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/produit/{id}', name: 'app_produit_show')]
    public function show(Produit $produit = null, EntityManagerInterface $em, Request $r, TranslatorInterface $translator): Response
    {
//        Si Produit = null on redirige l User
        if ($produit === null) {
            $this->addFlash('warning', $translator->trans('flash.cant'));
            return $this->redirectToRoute('app_produit_index');
        } else {
//            Sinon on verifie que l User est connecter
            $user = $this->getUser();
            $form = $this->createForm(ContenueType::class);
            if ($user) {
//                Si Oui on recupère / creer son panier actuel
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
<<<<<<< HEAD

=======
//                Si le form est valider on modifier/ Ajoute le COntenuPanier au panier
>>>>>>> 9d526a9a449da99ba29d457e65d3f640bdab7527
                if ($form->isSubmitted() && $form->isValid()) {
                    $commande = $em->getRepository(ContenuPanier::class)->findOneBy(['panier' => $panner]);
                    if ($commande) {

                        $produit->setStock($produit->getStock() + $commande->getQuantite());

                        if ($produit->getStock() >= $list->getQuantite()) {
                            $produit->setStock($produit->getStock() - $list->getQuantite());
                            $em->persist($produit);
                        } else {
                            $this->addFlash('warning', $translator->trans('flash.not_available'));
                            return $this->redirectToRoute('app_produit_show', ['id' => $produit->getId()]);
                        }
                    }
                    $em->persist($list);
                    $em->flush();
                    $this->addFlash('success', $translator->trans('flash.add_to_card'));
                }
            }
            return $this->render('produit/show.html.twig', [
                'produit' => $produit,
                'form' => $form,
            ]);
        }
    }


    #[Route('/produit/admin/{id}/edit', name: 'app_produit_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Produit $produit, ProduitRepository $produitRepository, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $logo = $form->get('photo')->getData();
            if ($logo) {
                unlink($this->getParameter('upload_dir') . '/' . $produit->getPhoto());
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
            $this->addFlash('success', $translator->trans('flash.edit_prod'));

            return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/produit/admin/delete/{id}', name: 'app_produit_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit, ProduitRepository $produitRepository, TranslatorInterface $translator): Response
    {
        if ($this->isCsrfTokenValid('delete' . $produit->getId(), $request->request->get('_token'))) {
            unlink($this->getParameter('upload_dir') . '/' . $produit->getPhoto());
            $produitRepository->remove($produit, true);
        }
        $this->addFlash('warning', $translator->trans('flash.remove_prod'));
        return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
    }
}
