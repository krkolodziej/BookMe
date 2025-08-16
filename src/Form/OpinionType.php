<?php

namespace App\Form;

use App\Entity\Opinion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class OpinionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('rating', HiddenType::class, [
            'constraints' => [
                new NotBlank([
                    'message' => 'Proszę wybrać ocenę.',
                ]),
                new Range([
                    'min' => 1,
                    'max' => 5,
                    'notInRangeMessage' => 'Ocena musi być pomiędzy {{ min }} a {{ max }}.',
                ]),
            ],
        ])
            ->add('content', TextareaType::class, [
                'label' => 'Komentarz',
                'required' => true,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Podziel się swoją opinią na temat usługi...',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Proszę dodać komentarz.',
                    ]),
                    new Length([
                        'min' => 10,
                        'max' => 1000,
                        'minMessage' => 'Komentarz musi zawierać co najmniej {{ limit }} znaków.',
                        'maxMessage' => 'Komentarz nie może zawierać więcej niż {{ limit }} znaków.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Opinion::class,
        ]);
    }
}