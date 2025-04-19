<?php

namespace App\Form;

use App\Entity\Categories;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CategoryFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'placeholder' => 'Entrez le nom de la catégorie',
                ],

                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un titre de catégorie',
                    ]),
                ],
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'placeholder' => 'Entrez la description de la catégorie',
                    // integrer un texte avec [...] en cliquant sur celui-ci je peux voir la suite
                    'rows' => 10,
                    'data' => 'description',
                    'maxlength' => 500,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir une description de catégorie',
                    ]),
                ],
            ])
            // ->add('date_creation', null, [
            //     'widget' => 'single_text',
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Categories::class,
        ]);
    }
}
