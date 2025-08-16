<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Url;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Imię jest wymagane.']),
                    new Length([
                        'max' => 50,
                        'maxMessage' => 'Imię nie może przekraczać 50 znaków.',
                    ]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Nazwisko jest wymagane.']),
                    new Length([
                        'max' => 50,
                        'maxMessage' => 'Nazwisko nie może przekraczać 50 znaków.',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Email jest wymagany.']),
                    new Email(['message' => 'Nieprawidłowy format adresu email.']),
                ],
            ])
            ->add('gender', ChoiceType::class, [
                'choices' => [
                    'Mężczyzna' => 'male',
                    'Kobieta' => 'female',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Płeć jest wymagana.']),
                ],
            ])
            ->add('avatarUrl', UrlType::class, [
                'required' => false,
                'constraints' => [
                    new Url(['message' => 'Podaj prawidłowy URL do pliku graficznego.']),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => ['label' => 'Hasło'],
                'second_options' => ['label' => 'Potwierdź hasło'],
                'invalid_message' => 'Hasło i potwierdzenie hasła muszą być identyczne.',
                'constraints' => [
                    new NotBlank(['message' => 'Hasło jest wymagane.']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Hasło musi mieć co najmniej 6 znaków.',
                    ]),
                ],
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