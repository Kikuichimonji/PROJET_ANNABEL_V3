<?php

namespace App\Controller;

use App\Entity\Consultation;
use App\Entity\Patient;
use App\Service\StatistiquesService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class StatistiquesController extends AbstractController
{
    #[Route('/statistiques', name: 'admin_statistiques')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(ManagerRegistry $doctrine, StatistiquesService $statistiquesService)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $patients = $doctrine->getRepository(Patient::class)->findAll();
        $consultations = $doctrine->getRepository(Consultation::class)->findAll();

        return $this->render('statistiques/index.html.twig', [
            'stats' => $statistiquesService->calculer($patients, $consultations),
        ]);
    }
}
