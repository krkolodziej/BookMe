<?php

namespace App\Form;

use App\Entity\Opinion;
use App\Entity\Booking;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

class AdminOpinionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $service = $options['service'] ?? null;
        $entity = $builder->getData();

        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Treść opinii',
                'attr' => [
                    'placeholder' => 'Wprowadź treść opinii...',
                    'class' => 'form-control',
                    'rows' => 5
                ]
            ])
            ->add('rating', ChoiceType::class, [
                'label' => 'Ocena',
                'choices' => [
                    '5 gwiazdek' => 5,
                    '4 gwiazdki' => 4,
                    '3 gwiazdki' => 3,
                    '2 gwiazdki' => 2,
                    '1 gwiazdka' => 1,
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => true,
                'attr' => [
                    'class' => 'rating-group'
                ]
            ]);

        $isNew = $entity === null || !method_exists($entity, 'getId') || $entity->getId() === null;

        if ($isNew) {
            $builder->add('booking', EntityType::class, [
                'class' => Booking::class,
                'choice_label' => function (Booking $booking) {
                    return sprintf(
                        '%s %s - %s - %s',
                        $booking->getUser()->getFirstName(),
                        $booking->getUser()->getLastName(),
                        $booking->getOffer()->getName(),
                        $booking->getStartTime()->format('d.m.Y H:i')
                    );
                },
                'choice_attr' => function (Booking $booking) {
                    $employeeInfo = '';
                    if ($booking->getEmployee()) {
                        $employeeInfo = $booking->getEmployee()->getUser()->getFirstName() . ' ' . 
                                        $booking->getEmployee()->getUser()->getLastName();
                    } else {
                        $employeeInfo = 'Brak przypisanego pracownika';
                    }
                    
                    return ['data-employee' => $employeeInfo];
                },
                'query_builder' => function (EntityRepository $er) use ($service) {
                    $qb = $er->createQueryBuilder('b')
                        ->where('b.service = :service')
                        ->setParameter('service', $service)
                        ->orderBy('b.startTime', 'DESC');

                    $qb->andWhere('NOT EXISTS (
                        SELECT o FROM App\Entity\Opinion o WHERE o.booking = b
                    )');

                    return $qb;
                },
                'placeholder' => 'Wybierz wizytę',
                'required' => true,
                'label' => 'Wizyta',
                'attr' => [
                    'class' => 'form-select'
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Opinion::class,
            'service' => null,
        ]);
    }
}