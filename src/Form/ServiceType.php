<?php

namespace App\Form;

use App\Entity\Service;
use App\Entity\ServiceCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nazwa Serwisu',
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Opis',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ])
            ->add('serviceCategory', EntityType::class, [
                'class' => ServiceCategory::class,
                'choice_label' => 'name',
                'placeholder' => 'Inne',
                'required' => false,
                'label' => 'Kategoria',
                'attr' => ['class' => 'form-select']
            ])
            ->add('imageUrl', UrlType::class, [
                'label' => 'Adres URL obrazka',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('city', TextType::class, [
                'label' => 'Miasto',
                'attr' => ['class' => 'form-control']
            ])
            ->add('street', TextType::class, [
                'label' => 'Ulica',
                'attr' => ['class' => 'form-control']
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Kod Pocztowy',
                'attr' => ['class' => 'form-control']
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'Numer Telefonu',
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Service::class,
        ]);
    }
}