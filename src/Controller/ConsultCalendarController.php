<?php

namespace App\Controller;


use App\Entity\ConsultCalendar;
use App\Form\ConsultCalendarType;
use App\Repository\ConsultCalendarRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/agenda')]
class ConsultCalendarController extends AbstractController
{
    #[Route('/list', name: 'agenda_list', methods: ['GET'])]
    public function index(ConsultCalendarRepository $consultCalendarRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('consult_calendar/index.html.twig', [
            'consult_calendars' => $consultCalendarRepository->findAll(),
        ]);
    }

    #[Route('/', name: 'agenda', methods: ['GET'])]
    public function fullCalendar(ConsultCalendarRepository $consultCalendarRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('consult_calendar/calendar.html.twig');
    }

    #[Route('/new', name: 'consult_calendar_new', methods: ['GET', 'POST'])]
    public function new(ManagerRegistry $doctrine, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $consultCalendar = new ConsultCalendar();
        $form = $this->createForm(ConsultCalendarType::class, $consultCalendar);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $doctrine->getManager();
            $entityManager->persist($consultCalendar);
            $entityManager->flush();

            return $this->redirectToRoute('agenda_list');
        }

        return $this->render('consult_calendar/new.html.twig', [
            'consult_calendar' => $consultCalendar,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'consult_show', methods: ['GET'])]
    public function show(ConsultCalendar $consultCalendar): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('consult_calendar/show.html.twig', [
            'consult_calendar' => $consultCalendar,
        ]);
    }

    #[Route('/{id}/edit', name: 'consult_calendar_edit', methods: ['GET', 'POST'])]
    public function edit(ManagerRegistry $doctrine, Request $request, ConsultCalendar $consultCalendar): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $form = $this->createForm(ConsultCalendarType::class, $consultCalendar);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $doctrine->getManager()->flush();

            return $this->redirectToRoute('agenda_list');
        }

        return $this->render('consult_calendar/edit.html.twig', [
            'consult_calendar' => $consultCalendar,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'consult_calendar_delete', methods: ['DELETE'])]
    public function delete(ManagerRegistry $doctrine, Request $request, ConsultCalendar $consultCalendar): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($this->isCsrfTokenValid('delete'.$consultCalendar->getId(), $request->request->get('_token'))) {
            $entityManager = $doctrine->getManager();
            $entityManager->remove($consultCalendar);
            $entityManager->flush();
        }

        return $this->redirectToRoute('agenda');
    }

    #[Route('/calendar', name: 'consult_calendar', methods: ['GET'])]
    public function calendar(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('consult_calendar/calendar.html.twig');
    }

    #[Route('/save', name: 'consult_calendar_save', methods: ['GET', 'POST'])]
    public function save(ManagerRegistry $doctrine, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $title = $request->request->get("title");

        $start= new \DateTime($request->request->get("start"));
        $end = new \DateTime($request->request->get("end"));


        $consultCalendar = new ConsultCalendar();
        $consultCalendar->setTitle($title);
        $consultCalendar->setStartDate($start);
        $consultCalendar->setEndDate($end);


        $entityManager = $doctrine->getManager();
        $entityManager->persist($consultCalendar);
        $entityManager->flush();
        

        return new Response('Done',200);
    }

    #[Route('/modify/{id}', name: 'consult_calendar_modify', methods: ['GET', 'POST'])]
    public function modify(ManagerRegistry $doctrine, Request $request,ConsultCalendar $consultCalendar): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $title = $request->request->get("title");

        $start= new \DateTime($request->request->get("start"));
        $end = new \DateTime($request->request->get("end"));


        $consultCalendar->setTitle($title);
        $consultCalendar->setStartDate($start);
        $consultCalendar->setEndDate($end);


        $entityManager = $doctrine->getManager();
        $entityManager->persist($consultCalendar);
        $entityManager->flush();
        

        return new Response('Done',200);
    }
}
