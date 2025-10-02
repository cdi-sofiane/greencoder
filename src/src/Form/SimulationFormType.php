<?php

namespace App\Form;

use App\Entity\Simulation;
use App\Entity\Video;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
/*todo factorisation des champs a valider*/
class SimulationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('uuid', TextType::class,['empty_data' => ''])
            ->add('name', TextType::class)
            ->add('ext', TextType::class)
            ->add('videoQuality', IntegerType::class)
            ->add('videoSize', IntegerType::class)
            ->add('videoLength', IntegerType::class)
            ->add('createdAt', DateType::class,['data' => new \DateTimeImmutable('now')])
            ->add('originalSize', IntegerType::class)
            ->add('estimateSize', IntegerType::class)
            ->add('gainPercentage', TextType::class)
            ->add('link', TextType::class)
        ;
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Simulation::class,
        ]);
    }
}