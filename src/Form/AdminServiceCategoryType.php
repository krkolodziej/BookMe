<?php

namespace App\Form;

use App\Entity\ServiceCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AdminServiceCategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nazwa kategorii',
                'constraints' => [
                    new NotBlank(['message' => 'Nazwa kategorii jest wymagana']),
                    new Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Nazwa kategorii musi mieć co najmniej {{ limit }} znaki',
                        'maxMessage' => 'Nazwa kategorii nie może być dłuższa niż {{ limit }} znaków'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Wprowadź nazwę kategorii'
                ]
            ])
            ->add('imageUrl', UrlType::class, [
                'label' => 'URL obrazu',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Wprowadź URL obrazu (opcjonalnie)'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ServiceCategory::class,
        ]);
    }
}