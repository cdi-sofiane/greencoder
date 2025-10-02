<?php

namespace App\Form;

use App\Form\DataTransformer\StringToArrayTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class VideoFilterType extends AbstractType
{
  private $stringToArrayTransformer;

  public function __construct(StringToArrayTransformer $stringToArrayTransformer)
  {
      $this->stringToArrayTransformer = $stringToArrayTransformer;
  }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('page', TextType::class, ['empty_data' => 1])
            ->add('order', TextType::class, ['empty_data' => 'ASC'])
            ->add('limit', TextType::class, ['empty_data' => 12])
            ->add('name', TextType::class, ['empty_data' => null])
            ->add('startAt', TextType::class, ['empty_data' => null])
            ->add('endAt', TextType::class, ['empty_data' => null])
            ->add('sortBy', TextType::class, ['empty_data' => null])
            ->add('mediaType', TextType::class, ['empty_data' => null])
            ->add('tags', TextType::class, ['empty_data' => null])
            ->add('encodingState', TextType::class, ['empty_data' => null])
            ->add('isStored', TextType::class, ['empty_data' => null])
            ->add('isDeleted', TextType::class, ['empty_data' => null])
            ->add('isInTrash', TextType::class, ['empty_data' => false])
            ->add('isMultiEncoded', TextType::class, ['empty_data' => null])
            ->add('user_uuid', TextType::class, ['empty_data' => null])
            ->add('folder_uuid', TextType::class, ['required' => true])
            ->add('account_uuid', TextType::class, ['required' => true]);

            $builder->get('mediaType')->addModelTransformer($this->stringToArrayTransformer);
            $builder->get('tags')->addModelTransformer($this->stringToArrayTransformer);
            $builder->get('encodingState')->addModelTransformer($this->stringToArrayTransformer);

            $builder->get('order')
                  ->addModelTransformer(new CallbackTransformer(
                      function ($originalDescription) {
                          return strtoupper($originalDescription);
                      },
                      function ($submittedDescription) {
                          return $submittedDescription;
                      }
            ));

            $builder->get('sortBy')
                  ->addModelTransformer(new CallbackTransformer(
                        function ($originalDescription) {
                            return $originalDescription;
                        },
                        function ($submittedDescription) {
                            return $submittedDescription == 'date' ? 'createdAt' : 'name';
                        }
                  ));
    }
}
