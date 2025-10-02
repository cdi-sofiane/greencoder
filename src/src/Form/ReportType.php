<?php

namespace App\Form;

use App\Entity\Report;
use App\Entity\User;
use DateTimeImmutable;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('uuid', TextType::class, ['empty_data' => 'd'])
            ->add('name', TextType::class, ['empty_data' => 'Rapport GreenEncoder - ' . (new DateTimeImmutable())->format('d-m-Y')])
            ->add('title', TextType::class)
            ->add('link', TextType::class)
            ->add('pdf', TextType::class)
            ->add('csv', TextType::class)
            ->add('totalVideos', NumberType::class)
            ->add('totalCarbon', NumberType::class)
            ->add('optimisation', NumberType::class)
            ->add('slugName', TextType::class)
            ->add('user', EntityType::class, ['class' => User::class, 'inherit_data' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Report::class,
        ]);
    }
}
