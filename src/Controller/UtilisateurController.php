<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UtilisateurController extends AbstractController
{
    #[Route('/utilisateur', name: 'admin_utilisateur')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(ManagerRegistry $doctrine)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $doctrine
        ->getRepository(Utilisateur::class)
        ->getAll();

        
        return $this->render("utilisateur/index.html.twig",[
            "users" => $user,
            ]);
    }

    #[Route('/utilisateur/edit/{id}', name: 'admin_edit_utilisateur')]
    #[IsGranted('ROLE_ADMIN')]
    public function editUser(ManagerRegistry $doctrine, Utilisateur $user,Request $request,UserPasswordHasherInterface $passwordHasher)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $entityManager = $doctrine->getManager();

        $form = $this->createForm(UtilisateurType::class,$user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get("password")->getData()
                )
            );
            $user->setRoles( $form->get("roles")->getData());
            $user->setUsername($form->get("username")->getData());
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute("admin_utilisateur",[
                ]);
        }

        return $this->render("utilisateur/editUser.html.twig",[
            "form" => $form->createView(),
            "users" => $user,
            ]); 
    }


    #[Route('/utilisateur/delete/{id}', name: 'admin_delete_utilisateur')]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteUtilisateur(ManagerRegistry $doctrine, Utilisateur $user)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $entityManager = $doctrine->getManager();
        
        $entityManager->remove($user);
        $entityManager->flush();

        return $this->redirectToRoute("app_login",[
            "id" => 0,
        ]);
    }
}
