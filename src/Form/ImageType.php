<?php

namespace App\Form;

use App\Entity\Image;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class ImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de l\'image',
                'attr' => [
                    'class' => 'w-full p-2 rounded-lg shadow-sm focus:ring-2 outline-none bg-slate-800 mt-2 focus:ring-blue-500',
                    'placeholder' => 'Entrez le titre de l\'image',
                ],
                'row_attr' => [
                    'class' => 'mb-4',
                ],
            ])
            ->add('file', FileType::class, [
                'label' => 'Fichier image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG, WEBP)',
                    ])
                ],
                'attr' => [
                    'class' => 'w-full p-2 rounded-lg shadow-sm cursor-pointer bg-slate-800 mt-2 focus:ring-2 focus:ring-blue-500',
                ],
                'row_attr' => [
                    'class' => 'mb-4',
                ],
            ])
            ->add('remove', CheckboxType::class, [
                'label' => 'Supprimer cette image',
                'required' => false,
                'mapped' => false, // Pas de liaison à l'entité Image
                'row_attr' => [
                    'class' => 'hidden',
                ],
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Image::class,
        ]);
    }
}
