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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class OeuvreController extends AbstractController
{
    #[Route('/oeuvre', name: 'oeuvre_index')]
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

            return $this->redirectToRoute('oeuvre_index');
        }

        return $this->render('oeuvre/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // #[Route('/oeuvre/{id}/edit', name: 'oeuvre_edit')]
    // public function edit(EntityManagerInterface $em, Oeuvre $oeuvre, Request $request, Filesystem $filesystem): Response
    // {
    //     $form = $this->createForm(OeuvreType::class, $oeuvre);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/oeuvres';

    //         // Gérer l'ajout de nouvelles images
    //         $images = $form->get('images')->getData(); // Récupérer les images soumises dans le formulaire
            
    //         foreach ($images as $index => $image) {
    //             $file = $form->get('images')[$index]->get('file')->getData(); // Accéder au champ file pour chaque image

    //             if ($file) {
    //                 // Vérifiez si un fichier a été téléchargé
    //                 $newFilename = 'oeuvre-' . uniqid() . '.' . $file->guessExtension();
    //                 $file->move($uploadDir, $newFilename);

    //                 // Mettre à jour le lien de l'image
    //                 $image->setLink($newFilename);
    //                 $image->setOeuvre($oeuvre); // Associer l'image à l'œuvre
    //             }

    //             // Vérifier si l'image est marquée pour suppression
    //             $remove = $form->get('images')[$index]->get('remove')->getData();

    //             if ($remove) {
    //                 // Si l'image est marquée pour suppression, supprimer le fichier du dossier
    //                 $link = $image->getLink(); // Récupérer le lien de l'image
    //                 if ($link && $filesystem->exists($uploadDir . '/' . $link)) {
    //                     $filesystem->remove($uploadDir . '/' . $link); // Supprimer le fichier
    //                 }
                    
    //                 // Supprimer l'image de la base de données
    //                 $em->remove($image);
    //             }
    //         }

    //         // Sauvegarder l'œuvre modifiée
    //         $em->persist($oeuvre);
    //         $em->flush();

    //         return $this->redirectToRoute('oeuvre_show', ['id' => $oeuvre->getId()]);
    //     }

    //     return $this->render('oeuvre/edit.html.twig', [
    //         'form' => $form->createView(),
    //         'oeuvre' => $oeuvre,
    //     ]);
    // }

    #[Route('/oeuvre/{id}/edit', name: 'oeuvre_edit')]
    public function edit(EntityManagerInterface $em, Oeuvre $oeuvre, Request $request, Filesystem $filesystem): Response
    {
        $form = $this->createForm(OeuvreType::class, $oeuvre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/oeuvres';

            // Gérer l'ajout de nouvelles images
            $images = $form->get('images')->getData(); // Récupérer les images soumises dans le formulaire

            foreach ($images as $index => $image) {
                $file = $form->get('images')[$index]->get('file')->getData(); // Accéder au champ file pour chaque image

                if ($file) {
                    // Vérifiez si un fichier a été téléchargé
                    $newFilename = 'oeuvre-' . uniqid() . '.' . $file->guessExtension();
                    $file->move($uploadDir, $newFilename);

                    // Mettre à jour le lien de l'image
                    $image->setLink($newFilename);
                    $image->setOeuvre($oeuvre); // Associer l'image à l'œuvre
                }

                // Vérifier si l'image est marquée pour suppression
                $remove = $form->get('images')[$index]->get('remove')->getData();

                if ($remove) {
                    // Si l'image est marquée pour suppression, supprimer le fichier du dossier
                    $link = $image->getLink(); // Récupérer le lien de l'image
                    if ($link && $filesystem->exists($uploadDir . '/' . $link)) {
                        $filesystem->remove($uploadDir . '/' . $link); // Supprimer le fichier
                    }

                    // Supprimer l'image de la base de données
                    $em->remove($image);
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

    #[Route('/oeuvre/image/{imageId}/delete', name: 'oeuvre_image_delete', methods: ['POST'])]
    public function deleteImage(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $imageId = $data['imageId']; // ID de l'image à supprimer

        // Logique pour supprimer l'image de la base de données
        // Exemple : $imageRepository->delete($imageId);

        // Retourner une réponse JSON pour confirmer la suppression
        return new JsonResponse(['status' => 'success', 'message' => 'Image supprimée']);
    }


    #[Route('/oeuvre/{id}', name: 'oeuvre_show')]
    public function show(Oeuvre $oeuvre): Response
    {
        return $this->render('oeuvre/show.html.twig', [
            'oeuvre' => $oeuvre,
        ]);
    }
}
