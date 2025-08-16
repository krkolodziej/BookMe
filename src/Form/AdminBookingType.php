<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Employee;
use App\Entity\Offer;
use App\Entity\Service;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class AdminBookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $service = $options['service'];
        $employees = $options['employees'];
        $today = new \DateTimeImmutable('today');

        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getEmail() . ' (' . $user->getFirstName() . ' ' . $user->getLastName() . ')';
                },
                'label' => 'Klient',
                'required' => true,
                'attr' => ['class' => 'form-select']
            ])
            ->add('offer', EntityType::class, [
                'class' => Offer::class,
                'choices' => $service->getOffers(),
                'choice_label' => function (Offer $offer) {
                    return $offer->getName() . ' (' . $offer->getDuration() . ' min, ' . $offer->getPrice() . ' zł)';
                },
                'label' => 'Usługa',
                'required' => true,
                'attr' => ['class' => 'form-select']
            ])
            ->add('employee', EntityType::class, [
                'class' => Employee::class,
                'choices' => $employees,
                'choice_label' => function (Employee $employee) {
                    return $employee->getUser()->getFirstName() . ' ' . $employee->getUser()->getLastName();
                },
                'label' => 'Pracownik',
                'required' => true,
                'attr' => ['class' => 'form-select']
            ]);

        $builder->add('bookingDate', DateType::class, [
            'label' => 'Data wizyty',
            'mapped' => false,
            'widget' => 'single_text',
            'required' => true,
            'attr' => [
                'class' => 'form-control date-picker',
                'min' => $today->format('Y-m-d')
            ],
            'html5' => true,
            'constraints' => [
                new GreaterThanOrEqual([
                    'value' => $today,
                    'message' => 'Data wizyty nie może być z przeszłości.'
                ])
            ]
        ]);

        $builder->add('startTime', DateTimeType::class, [
            'widget' => 'single_text',
            'html5' => true,
            'attr' => ['class' => 'hidden-start-time', 'style' => 'display: none;'],
            'constraints' => [
                new GreaterThanOrEqual([
                    'value' => new \DateTimeImmutable(),
                    'message' => 'Czas rozpoczęcia wizyty nie może być z przeszłości.'
                ])
            ]
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($today) {
            $booking = $event->getData();
            $form = $event->getForm();

            if ($booking && $booking->getStartTime()) {
                $form->add('bookingDate', DateType::class, [
                    'label' => 'Data wizyty',
                    'mapped' => false,
                    'widget' => 'single_text',
                    'required' => true,
                    'attr' => [
                        'class' => 'form-control date-picker',
                        'min' => $today->format('Y-m-d')
                    ],
                    'html5' => true,
                    'data' => $booking->getStartTime(),
                    'constraints' => [
                        new GreaterThanOrEqual([
                            'value' => $today,
                            'message' => 'Data wizyty nie może być z przeszłości.'
                        ])
                    ]
                ]);
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $booking = $event->getData();

            if ($booking && $booking->getStartTime() && $booking->getOffer()) {
                $booking->setEndTime();
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
        ]);

        $resolver->setRequired(['service', 'employees']);
    }
}