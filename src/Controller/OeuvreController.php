<?php

namespace App\Controller;

use App\Entity\Oeuvre;
use App\Form\OeuvreType;
use App\Repository\OeuvreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
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

    #[Route('/oeuvre/{id}/edit', name: 'oeuvre_edit')]
    public function edit(EntityManagerInterface $em, Oeuvre $oeuvre, Request $request, Filesystem $filesystem): Response
    {
        $form = $this->createForm(OeuvreType::class, $oeuvre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/oeuvres';

            // Gérer la suppression des images
            foreach ($oeuvre->getImages() as $image) {
                if ($image->getRemove()) {
                    // Supprimer l'image du disque
                    $filePath = $uploadDir . '/' . $image->getLink();
                    if (file_exists($filePath)) {
                        $filesystem->remove($filePath);
                    }

                    // Supprimer l'image de la base de données
                    $oeuvre->removeImage($image);
                }
            }

            // Gérer l'ajout de nouvelles images
            $images = $form->get('images')->getData(); // Récupérer les images soumises dans le formulaire

            foreach ($images as $index => $image) {
                $file = $form->get('images')[$index]->get('file')->getData(); // Accéder au champ `file` pour chaque image

                if ($file) {
                    // Vérifiez si un fichier a été téléchargé
                    $newFilename = 'oeuvre-' . uniqid() . '.' . $file->guessExtension();
                    $file->move($uploadDir, $newFilename);

                    // Mettre à jour le lien de l'image
                    $image->setLink($newFilename);
                    $image->setOeuvre($oeuvre); // Associer l'image à l'œuvre
                }
            }

            // Sauvegarder l'œuvre modifiée
            $em->persist($oeuvre);
            $em->flush();

            return $this->redirectToRoute('oeuvre_show', ['id' => $oeuvre->getId()]);
        }

        return $this->render('oeuvre/edit.html.twig', [
            'form' => $form->createView(),
            'oeuvre' => $oeuvre,
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
