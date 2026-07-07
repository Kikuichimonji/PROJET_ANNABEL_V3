<?php

namespace App\Controller;

use App\Entity\Cabinet;
use App\Entity\Utilisateur;
use App\Form\RegistrationFormType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends AbstractController
{
    /**
     * @Route("/", name="app_login")
     */
    public function login(ManagerRegistry $doctrine, AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $cabinet = $doctrine
            ->getRepository(Cabinet::class)
            ->getAll();


        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
             'error' => $error,
             "cabinets" => $cabinet
             ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @Route("/addUser", name="add_user")
     */
    public function register(ManagerRegistry $doctrine, Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new Utilisateur();

        $form = $this->createForm(RegistrationFormType::class, $user);
        if(!$this->isGranted('ROLE_ADMIN'))
            $form->remove("roles");

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
        

            $entityManager = $doctrine->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/addUser.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
