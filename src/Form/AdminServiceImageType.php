<?php

namespace App\Form;

use App\Entity\ServiceImage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

class AdminServiceImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('url', UrlType::class, [
                'label' => 'URL Zdjęcia',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Wprowadź URL zdjęcia (np. https://example.com/image.jpg)'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Proszę podać URL zdjęcia']),
                    new Url(['message' => 'Proszę podać prawidłowy URL'])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ServiceImage::class,
        ]);
    }
}