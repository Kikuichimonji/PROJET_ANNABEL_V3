<?php

namespace App\Controller;

use App\Entity\Cabinet;
use App\Entity\Patient;
use App\Form\PatientType;
use App\Entity\Utilisateur;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PatientController extends AbstractController
{
    #[Route('/patient/{idc}', name: 'patient_add', defaults: ['_fragment' => 'consultation'])]
    #[Route('/patient/{idc}/{id}', name: 'patient_edit', defaults: ['_fragment' => 'consultation'])]
    public function addPatient(ManagerRegistry $doctrine, Request $request, #[MapEntity(id: 'id')] Patient $patient = null, $idc = null, $err = null)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $newPatient = 0;
        if(!$patient)
        {
            $patient = new Patient();
            $newPatient = 1;
            $patient->setPrenom('Vide');
            $patient->setNom("Vide");
            $patient->setDateNaissance(new \DateTime());
        }
        if(!$err)
            $err = 1;

        $cabinets = $doctrine
            ->getRepository(Cabinet::class)
            ->getAll();
        $utilisateurs = $doctrine->getRepository(Utilisateur::class)->getAll();

        $isIn = 0;
        if(is_numeric($idc))   
            foreach($cabinets as $c)
                if($c->getId() == $idc)
                    $isIn = 1;

        if(!$isIn)
            return $this->redirectToRoute("home_detail",[
                "id" => $idc,
                "err" => $err,
                ]);
                
        
        $entityManager = $doctrine->getManager();
        $form = $this->createForm(PatientType::class,$patient);
        $form->handleRequest($request);
        //dd($form->getData());


        if($form->isSubmitted() && $form->isValid())
        {
            $patient = $form->getData();
            //dd($patient);
            $entityManager->persist($patient,$cabinets);
            $entityManager->flush();

            return $this->redirectToRoute("patient_edit",[
                "id" => $patient->getId(),
                "idc" => $idc,
                ]);
        }

        return $this->render("patient/index.html.twig",[
            "form" => $form->createView(),
            "patient" => $patient,
            "cabinets" => $cabinets,
            "utilisateurs" => $utilisateurs,
            "new" => $newPatient,
        ]);
    }

    #[Route('/DeletePatient/{id}', name: 'patient_delete_full')]
    #[Route('/DeletePatient/{idc}/{id}', name: 'patient_delete_cabinet')]
    #[IsGranted('ROLE_ADMIN')]
    public function deletePatient(ManagerRegistry $doctrine, Request $request, Patient $patient, $idc = 0)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $entityManager = $doctrine->getManager();
        if(!$idc)
        {
            $session = $request->getSession();
            if(!count($patient->getConsultations()))
            {
                $doctrine
                ->getRepository(Patient::class)
                ->deletePatientById($entityManager,$patient->getId());
            }
            else
                return $this->redirectToRoute("home_detail",[
                    "id" => $session->get("cabinet")->getId(),
                    "err" => 2
                ]);
        }
        else
        {
            $cabinet = $doctrine
            ->getRepository(Cabinet::class)
            ->getOneById($idc);
            $cabinet[0]->removePatient($patient);
            $entityManager->flush();
        }
        return $this->redirectToRoute("home_detail",[
            "id" => $idc,
        ]);
        
        
    }
}
