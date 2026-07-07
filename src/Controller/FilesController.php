<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class FilesController extends AbstractController
{
    #[Route('/files', name: 'files')]
    public function index()
    {
        return $this->render('files/index.html.twig', [
            'controller_name' => 'FilesController',
        ]);
    }
}
