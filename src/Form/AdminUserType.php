<?php

namespace App\Form;

use App\Entity\Service;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

class AdminUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;

        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Imię',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Wprowadź imię użytkownika'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Proszę podać imię']),
                    new Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Imię musi mieć co najmniej {{ limit }} znaki',
                        'maxMessage' => 'Imię nie może być dłuższe niż {{ limit }} znaków'
                    ])
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nazwisko',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Wprowadź nazwisko użytkownika'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Proszę podać nazwisko']),
                    new Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Nazwisko musi mieć co najmniej {{ limit }} znaki',
                        'maxMessage' => 'Nazwisko nie może być dłuższe niż {{ limit }} znaków'
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Wprowadź adres email'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Proszę podać adres email']),
                    new Email(['message' => 'Proszę podać prawidłowy adres email'])
                ]
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Płeć',
                'choices' => [
                    'Mężczyzna' => 'male',
                    'Kobieta' => 'female',
                ],
                'attr' => [
                    'class' => 'form-select'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Proszę wybrać płeć'])
                ]
            ])
            ->add('avatarUrl', UrlType::class, [
                'label' => 'URL Zdjęcia',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Wprowadź URL zdjęcia użytkownika (opcjonalnie)'
                ],
                'constraints' => [
                    new Url(['message' => 'Proszę podać prawidłowy URL'])
                ]
            ])
            ->add('isAdmin', CheckboxType::class, [
                'label' => 'Administrator',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ]);

        if ($isEdit) {
            $builder->add('plainPassword', PasswordType::class, [
                'label' => 'Nowe hasło (pozostaw puste, aby nie zmieniać)',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'new-password'
                ],
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Hasło musi mieć co najmniej {{ limit }} znaków',
                        'max' => 4096,
                    ]),
                ]
            ]);
        } else {
            $builder->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Hasła muszą być identyczne',
                'options' => ['attr' => ['class' => 'form-control', 'autocomplete' => 'new-password']],
                'required' => true,
                'first_options' => ['label' => 'Hasło'],
                'second_options' => ['label' => 'Powtórz hasło'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Proszę podać hasło',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Hasło musi mieć co najmniej {{ limit }} znaków',
                        'max' => 4096,
                    ]),
                ],
            ])
                ->add('createAsEmployee', CheckboxType::class, [
                    'label' => 'Utwórz jako pracownika',
                    'required' => false,
                    'mapped' => false,
                    'attr' => [
                        'class' => 'form-check-input',
                        'data-toggle' => 'employee-service-section'
                    ]
                ])
                ->add('service', EntityType::class, [
                    'class' => Service::class,
                    'choice_label' => 'name',
                    'placeholder' => 'Wybierz serwis',
                    'label' => 'Serwis',
                    'mapped' => false,
                    'required' => false,
                    'attr' => [
                        'class' => 'form-select searchable-dropdown',
                        'data-search' => 'true'
                    ]
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
            'compound' => true,
        ]);
    }
}