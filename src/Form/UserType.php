<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Imię',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Wpisz swoje imię'
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nazwisko',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Wpisz swoje nazwisko'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adres e-mail',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Wpisz swój adres e-mail'
                ]
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Płeć',
                'choices' => [
                    'Mężczyzna' => 'male',
                    'Kobieta' => 'female',
                    'Inne' => 'other'
                ],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('avatarUrl', UrlType::class, [
                'label' => 'URL Zdjęcia Profilowego',
                'required' => false,
                'constraints' => [
                    new Url([
                        'message' => 'Proszę podać prawidłowy URL'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://przyklad.pl/twoje-zdjecie.jpg'
                ]
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Nowe hasło',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Twoje hasło powinno mieć co najmniej {{ limit }} znaków',
                        'max' => 4096,
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Pozostaw puste, aby zachować obecne hasło'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}