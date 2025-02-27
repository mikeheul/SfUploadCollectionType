<?php

namespace App\Controller;

use App\Entity\Oeuvre;
use App\Form\OeuvreType;
use App\Repository\OeuvreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class OeuvreController extends AbstractController
{
    #[Route('/oeuvre', name: 'app_oeuvre')]
    public function index(OeuvreRepository $or): Response
    {
        $oeuvres = $or->findAll();

        return $this->render('oeuvre/index.html.twig', [
            'oeuvres' => $oeuvres,
        ]);
    }

    #[Route('/oeuvre/new', name: 'oeuvre_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $oeuvre = new Oeuvre();
        $form = $this->createForm(OeuvreType::class, $oeuvre);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $oeuvre->generateSlug($slugger);

            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/oeuvres';

            foreach ($form->get('images') as $imageForm) {
                /** @var UploadedFile $file */
                $file = $imageForm->get('file')->getData();
                if ($file) {
                    $newFilename = 'oeuvre-' . uniqid() . '.' . $file->guessExtension();
                    $file->move($uploadDir, $newFilename);
            
                    $image = $imageForm->getData(); // Récupérer l'objet Image
                    $image->setLink($newFilename);
                    $image->setOeuvre($oeuvre);
                }
            }

            $entityManager->persist($oeuvre);
            $entityManager->flush();

            return $this->redirectToRoute('app_oeuvre');
        }

        return $this->render('oeuvre/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/oeuvre/{id}', name: 'oeuvre_show')]
    public function show(Oeuvre $oeuvre): Response
    {
        return $this->render('oeuvre/show.html.twig', [
            'oeuvre' => $oeuvre,
        ]);
    }
}
