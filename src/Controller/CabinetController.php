<?php

namespace App\Controller;

use App\Entity\Cabinet;
use App\Form\CabinetType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CabinetController extends AbstractController
{
    /**
     * @Route("/cabinet", name="admin_cabinet")
     * @IsGranted("ROLE_ADMIN")
     */
    public function index(ManagerRegistry $doctrine)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $cabinets = $doctrine
        ->getRepository(Cabinet::class)
        ->getAll();

        return $this->render('cabinet/index.html.twig', [
            'cabinets' => $cabinets,
        ]);
    }

    /**
     * @Route("/cabinet/Edit", name="admin_add_cabinet")
     * @Route("/cabinet/Edit/{id}", name="admin_edit_cabinet")
     * @IsGranted("ROLE_ADMIN")
     */
    public function editUser(ManagerRegistry $doctrine, Request $request, Cabinet $cabinet = null)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if(!$cabinet)
            $cabinet = new Cabinet();

        $entityManager = $doctrine->getManager();

        $form = $this->createForm(CabinetType::class,$cabinet);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $cabinet = $form->getData();
            $entityManager->persist($cabinet);
            $entityManager->flush();

            return $this->redirectToRoute("admin_cabinet",[
                ]);
        }

        return $this->render("cabinet/addEdit.html.twig",[
            "form" => $form->createView(),
            "users" => $cabinet,
            ]); 
    }


    /**
     * @Route("/cabinet/delete/{id}", name="admin_delete_cabinet")
     * @IsGranted("ROLE_ADMIN")
     */
    public function deleteUtilisateur(ManagerRegistry $doctrine, Cabinet $cabinet)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $entityManager = $doctrine->getManager();
        
        $entityManager->remove($cabinet);
        $entityManager->flush();

        return $this->redirectToRoute("admin_cabinet");
    }
}
