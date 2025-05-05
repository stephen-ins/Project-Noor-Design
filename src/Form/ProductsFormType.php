<?php

namespace App\Form;

use App\Entity\Categories;
use App\Entity\Products;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\File;

class ProductsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du produit',
                'required' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un nom de produit.',
                    ]),
                ],
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description du produit',
                'required' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer une description.',
                    ]),
                ],
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Description détaillée du produit',
                ],
            ])

            ->add('prix', NumberType::class, [
                'label' => 'Prix du produit',
                'required' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un prix.',
                    ]),
                ],
                'attr' => [
                    'min' => 0,
                ],
            ])

            ->add('stock', IntegerType::class, [
                'label' => 'Quantité en stock',
                'required' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer une quantité.',
                    ]),
                ],
                'attr' => [
                    'min' => 0,
                    'placeholder' => 'Votre stock actuel',
                ],
            ])

            ->add('image', FileType::class, [
                'label' => 'Image principale',
                // Gestion de l upload separé
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '50M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, WEBP)',
                    ])
                ],
            ])

            ->add('additionalImages', FileType::class, [
                'label' => 'Images additionnelles',
                // Gestion de l upload separé
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'attr' => [
                    'accept' => 'image/*',
                    'multiple' => 'multiple',
                    'class' => 'form-control-file',
                ],
                'constraints' => [
                    new Count([
                        'max' => 2,
                        'maxMessage' => 'Vous ne pouvez pas télécharger plus de 2 images additionnelles.',
                    ])
                ],
            ])

            ->add('materiaux', TextType::class, [
                'label' => 'Matériaux utilisés',
                'required' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer les matériaux.',
                    ]),
                ],
            ])

            ->add('taille', TextType::class, [
                'label' => 'Dimensions du produit',
                'required' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer les dimensions.',
                    ]),
                ],
            ])

            ->add('poids', NumberType::class, [
                'label' => 'Poids du produit',
                'required' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer le poids.',
                    ]),
                ],
                'attr' => [
                    'min' => 0,
                ],
            ])

            ->add('date_ajout', null, [
                'widget' => 'single_text',
                'required' => false,
                'data' => new \DateTimeImmutable(),
            ])

            ->add('categorie', EntityType::class, [
                'class' => Categories::class,
                'choice_label' => 'nom', // Utilisation du nom correct du champ dans Categories
                'required' => true,
                'placeholder' => 'Sélectionnez une catégorie',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Products::class,
        ]);
    }
}
