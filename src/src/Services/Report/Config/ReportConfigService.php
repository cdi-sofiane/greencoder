<?php

namespace App\Services\Report\Config;

use App\Entity\ReportConfig;
use App\Entity\User;
use App\Form\ReportConfigType;
use App\Repository\ReportConfigRepository;
use App\Services\JsonResponseMessage;
use DateTimeImmutable;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class ReportConfigService
{
    /**
     * Undocumented variable
     *
     * @var FormFactory $formFactory;
     */
    public $formFactory;
    public $reportConfigRepository;
    public $serializer;

    public function __construct(
        FormFactoryInterface $formFactory,
        ReportConfigRepository $reportConfigRepository,
        SerializerInterface $serializer
    ) {
        $this->formFactory = $formFactory;
        $this->reportConfigRepository = $reportConfigRepository;
        $this->serializer = $serializer;
    }



    public function createUserReportConfig($data = null, $account = null)
    {
        $data = ['account' => $account];

        $reportConfig = new ReportConfig();
        $form = $this->formFactory->create(ReportConfigType::class, $reportConfig);

        $formData = $form->submit($data);
        $formData->getData()->setCreatedAt(new DateTimeImmutable('now'));
        $formData->getData()->setUpdatedAt(new DateTimeImmutable('now'));
        $formData->getData()->setAccount($data['account']);
        return $formData->getData();
    }

    public function editReportConfig($body,  $reportConfig, $group)
    {


        $data = $this->serializer->deserialize(
            $body,
            ReportConfig::class,
            'json',
            [
                'object_to_populate' => $reportConfig,
                "groups" => $group,
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true
            ],

        );
        return $data;
    }
}
