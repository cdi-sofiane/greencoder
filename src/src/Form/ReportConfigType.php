<?php

namespace App\Form;

use App\Entity\ReportConfig;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\DBAL\Types\DateTimeImmutableType;
use Doctrine\DBAL\Types\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType as TypeTextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReportConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('uuid', TypeTextType::class, [
                'empty_data' => '',
            ])
            ->add('totalCompletion', IntegerType::class, [
                'empty_data' => "100",
            ])
            ->add('totalViews', IntegerType::class, [
                'empty_data' => "100000",
            ])
            ->add('mobileCarbonWeight', IntegerType::class, [
                'empty_data' => "50",
            ])
            ->add('mobileRepartition', IntegerType::class, [
                'empty_data' => "80",
            ])
            ->add('desktopCarbonWeight', IntegerType::class, [
                'empty_data' => "18",
            ])
            ->add('desktopRepartition', IntegerType::class, [
                'empty_data' => "20",
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReportConfig::class,
        ]);
    }
}
