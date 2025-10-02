<?php

namespace App\Form;

use App\Entity\Video;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VideoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('duration', TextType::class)
            ->add('progress', TextType::class, ['empty_data' => 0])
            ->add('isMultiEncoded', TextType::class, ['empty_data' => ''])
            ->add('isStored', TextType::class)
            ->add('qualityNeed', TextType::class, ['empty_data' => null])
            ->add('size', TextType::class)
            ->add('videoQuality', TextType::class, ['empty_data' => ''])
            ->add('link', TextType::class)
            ->add('jobId', TextType::class)
            ->add('downloadLink', TextType::class)
            ->add('streamLink', TextType::class)
            ->add('thumbnail', TextType::class)
            ->add('mediaType', TextType::class, ["empty_data" => 'DEFAULT'])
            ->add('extension', TextType::class)
            ->add('isInTrash', TextType::class, ['empty_data' => false])
            ->add('isArchived', TextType::class, ['empty_data' => false])
            ->add('isDeleted', TextType::class, ['empty_data' => false])
            ->add('isUploadComplete', TextType::class, ['empty_data' => false])
            ->add('title', TextType::class, ['empty_data' => null])
            ->add('maxDownloadAuthorized', TextType::class)
            ->add('encodingState', TextType::class, ['empty_data' => Video::ENCODING_PENDING])
            ->add('gainOptimisation', TextType::class, ['empty_data' => 0])
            ->add('deletedAt', TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Video::class,
        ]);
    }
}
