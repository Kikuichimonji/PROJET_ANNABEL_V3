<?php

namespace App\Controller;

use App\Entity\Cabinet;
use App\Entity\Patient;
use App\Data\SearchData;
use App\Form\SearchType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    /**
     * @Route("/accueil", name="home")
     * @Route("/accueil/{id}", name="home_detail")
     */
    public function index(ManagerRegistry $doctrine, Cabinet $cabinet = null,Request $request)
    {
        //Si l'utilisateur n'est pas autentifié il sera redirigé vers la page de connexion
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        //On récupère la liste de tout les cabinets
        $cabinets = $doctrine
            ->getRepository(Cabinet::class)
            ->getAll();
        
        //Si il n'y a pas de cabinet dans l'id de la requete on prend le 1er existant dans la liste par défaut
        if(!$cabinet)
        {
            return $this->redirectToRoute("home_detail",[
                "id" => $cabinets[0]->getId(),
                "err" => 1
                ]);
        }
        //On récupère la session pour y insérer le cabinet envoyé 
        $session = $request->getSession();
        $session->set("cabinet",$cabinet);

        //On initialise la pagination pour atterrir sur la page 1
        $data = new SearchData();
        $data->page = $request->get('page', 1);

        //On initialise le formulaire de recherche pour la liste des patients
        $form = $this->createForm(SearchType::class, $data);
        $form->handleRequest($request);

        //On vérifie sur le formulare de recherche est valide 
        if($form->isSubmitted() && $form->isValid())
        {
            //On récupère la liste des patients qui corresponde aux critères
            $patients = $doctrine
                        ->getRepository(Patient::class)
                        ->getBySearch($data);
        }
        else
        {
            //Si il y a soucis avec le formulaire on renvoie la liste entière des patients
            $patients = $doctrine
            ->getRepository(Patient::class)
            ->getAll($data);
        }
    
        
        //On redirige sur la liste des patients avec toute les données nécessaire
        return $this->render('home/index.html.twig', [
            "patients" => $patients,
            "cabinets" => $cabinets,
            'form' => $form->createView(),
        ]);
    }

}
