<?php

namespace App\Form;

use App\Entity\OpeningHour;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class OpeningHourType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $allDays = [
            'Poniedziałek' => 'Poniedziałek',
            'Wtorek' => 'Wtorek',
            'Środa' => 'Środa',
            'Czwartek' => 'Czwartek',
            'Piątek' => 'Piątek',
            'Sobota' => 'Sobota',
            'Niedziela' => 'Niedziela'
        ];

        if (!empty($options['exclude_days'])) {
            foreach ($options['exclude_days'] as $day) {
                unset($allDays[$day]);
            }
        }

        $builder
            ->add('dayOfWeek', ChoiceType::class, [
                'label' => 'Dzień tygodnia',
                'choices' => $allDays,
                'placeholder' => 'Wybierz dzień tygodnia',
                'required' => true,
                'disabled' => $options['is_edit'] && empty($allDays)
            ])
            ->add('openingTime', TimeType::class, [
                'label' => 'Godzina otwarcia',
                'widget' => 'single_text',
                'required' => true,
                'disabled' => $options['data']->isClosed()
            ])
            ->add('closingTime', TimeType::class, [
                'label' => 'Godzina zamknięcia',
                'widget' => 'single_text',
                'required' => true,
                'disabled' => $options['data']->isClosed()
            ])
            ->add('closed', CheckboxType::class, [
                'label' => 'Zamknięte w ten dzień',
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OpeningHour::class,
            'exclude_days' => [],
            'is_edit' => false,
        ]);

        $resolver->setAllowedTypes('exclude_days', 'array');
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}