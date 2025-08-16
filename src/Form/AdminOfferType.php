<?php

namespace App\Form;

use App\Entity\Offer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Range;

class AdminOfferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nazwa Oferty',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Wprowadź nazwę oferty'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Proszę podać nazwę oferty']),
                    new Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Nazwa oferty musi mieć co najmniej {{ limit }} znaki',
                        'maxMessage' => 'Nazwa oferty nie może być dłuższa niż {{ limit }} znaków'
                    ])
                ]
            ])
            ->add('duration', IntegerType::class, [
                'label' => 'Czas Trwania (minuty)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Wprowadź czas trwania w minutach'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Proszę podać czas trwania']),
                    new Positive(['message' => 'Czas trwania musi być dodatni']),
                    new Range([
                        'min' => 5,
                        'max' => 480,
                        'minMessage' => 'Czas trwania musi wynosić co najmniej {{ limit }} minut',
                        'maxMessage' => 'Czas trwania nie może przekraczać {{ limit }} minut (8 godzin)'
                    ])
                ]
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Cena (PLN)',
                'currency' => 'PLN',
                'divisor' => 1,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Wprowadź cenę'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Proszę podać cenę']),
                    new Positive(['message' => 'Cena musi być dodatnia'])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offer::class,
        ]);
    }
}