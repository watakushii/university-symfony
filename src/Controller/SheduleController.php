<?php

namespace App\Controller;

use App\Entity\Shedule;
use App\Form\SheduleType;
use App\Repository\SheduleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/shedule')]
final class SheduleController extends AbstractController
{
    #[Route(name: 'app_shedule_index', methods: ['GET'])]
    public function index(SheduleRepository $sheduleRepository): Response
    {
        return $this->render('shedule/index.html.twig', [
            'shedules' => $sheduleRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_shedule_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $shedule = new Shedule();
        $form = $this->createForm(SheduleType::class, $shedule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($shedule);
            $entityManager->flush();

            return $this->redirectToRoute('app_shedule_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('shedule/new.html.twig', [
            'shedule' => $shedule,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_shedule_show', methods: ['GET'])]
    public function show(Shedule $shedule): Response
    {
        return $this->render('shedule/show.html.twig', [
            'shedule' => $shedule,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_shedule_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Shedule $shedule, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SheduleType::class, $shedule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_shedule_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('shedule/edit.html.twig', [
            'shedule' => $shedule,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_shedule_delete', methods: ['POST'])]
    public function delete(Request $request, Shedule $shedule, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$shedule->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($shedule);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_shedule_index', [], Response::HTTP_SEE_OTHER);
    }
}
