<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class TrashFilterType extends AbstractType
{


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('order', TextType::class, ['empty_data' => 'ASC'])
            ->add('search', TextType::class, ['empty_data' => null])
            ->add('sortBy', TextType::class, ['empty_data' => null]);

            $builder->get('order')
                  ->addModelTransformer(new CallbackTransformer(
                      function ($original) {
                          return strtoupper($original);
                      },
                      function ($submitted) {
                          return $submitted;
                      }
            ));

            $builder->get('sortBy')
                  ->addModelTransformer(new CallbackTransformer(
                        function ($original) {
                            return $original;
                        },
                        function ($submitted) {
                            return $submitted == 'date' ? 'createdAt' : 'name';
                        }
                  ));
    }
}
