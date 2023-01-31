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
        //        On verifie si l'utilisateur est bien connecter'
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        //        Si l'utilisateur est Super Admin on affiche tout les panier non acheter
        if (in_array('ROLE_MODERATOR', $user->getRoles())) {

            return $this->render('panier/index.html.twig', [
                'paniers' => $panierRepository->findBy(['etat' => false]),
            ]);
        } else {
            //            Sinon on recupere le dernier panier avec l etat false de l utilisateur
            $panier = $panierRepository->findOneBy(['user' => $user, 'etat' => false]);
            //            Si il n'en possède pas on lui ne créer un'
            if (!$panier) {
                $panier = new Panier();
                $panier->setUser($this->getUser())
                    ->setEtat(false)
                    ->setDate(new \DateTime());
                $em->persist($panier);
                $em->flush();
            }
            //            Puis on affiche son pannier
            return $this->render('panier/show.html.twig', [
                'panier' => $panier,
            ]);
        }
    }

    #[Route('/{id}', name: 'app_panier_show', methods: ['GET'])]
    public function show(Panier $panier, TranslatorInterface $translator): Response
    {
        //        Si l Utilisateur est connecter
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        //        Si le panier apartien a l user ou si l User est SuperAdmin on lui affiche le panier grace a son ID
        if ($user === $panier->getUser() || in_array('ROLE_MODERATOR', $user->getRoles())) {
            return $this->render('panier/show.html.twig', [
                'panier' => $panier,
            ]);
        } else {
            //            Sinon on le redirige vers la page d aceuille avec un message flash
            $this->addFlash('warning', $translator->trans('flash.cant'));
            return $this->redirectToRoute('app_produit_index');
        }
    }

    #[Route('line/{id}', name: 'app_panier_remove_line')]
    public function removeLigne(ContenuPanier $contenu, EntityManagerInterface $em, Request $r, TranslatorInterface $translator)
    {
        //        On verifie si le panier appartien a l user
        if ($contenu->getPanier()->getUser() !== $this->getUser()) {
            //            Si Non on redirige vers une page et on affiche un msg flash
            $this->addFlash('warning', $translator->trans('flash.cant'));
            return $this->redirectToRoute('app_panier_show', ['id' => $contenu->getPanier()->getId()]);
        } else {
            //            Si Oui On SUpprime la ligne
            $em->remove($contenu);
            $em->flush();
            $this->addFlash('warning', $translator->trans('flash.remove_prod'));

            return $this->redirectToRoute('app_panier_index');
        }
    }

    #[Route('valid/{id}', name: 'app_panier_valid')]
    public function validPanier(Panier $panner, EntityManagerInterface $em, Request $r, TranslatorInterface $translator)
    {
        //        On verifie si le panier appartien a l User
        if ($panner->getUser() !== $this->getUser()) {
            //            Si Non on redirige vers une autre page et on affiche un msg flash
            $this->addFlash('warning', $translator->trans('flash.cant'));
            return $this->redirectToRoute('app_panier_show', ['id' => $panner->getId()]);
        } else {
            $contenus = $panner->getContenuPaniers();
//            On verifie si les produits sont en stock
            foreach ($contenus as $contenu) {
                $produit = $contenu->getProduit();
                if ($produit->getStock() < $contenu->getQuantite()) {
                    $this->addFlash('warning', $translator->trans('flash.not_available'));
                    return $this->redirectToRoute('app_panier_show', ['id' => $panner->getId()]);
                }
            }

            // Mets à jour l'état de la commande et le stock des produits
            $panner->setEtat(true);
            $panner->setDate(new \DateTime());
            $em->persist($panner);

            foreach ($contenus as $contenu) {
                $produit = $contenu->getProduit();
                $produit->setStock($produit->getStock() - $contenu->getQuantite());
                $em->persist($produit);
            }
            $em->flush();
            $this->addFlash('success', $translator->trans('flash.buy'));

            return $this->redirectToRoute('app_produit_index');
        }
    }
}
