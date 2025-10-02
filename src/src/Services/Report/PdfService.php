<?php

namespace App\Services\Report;

use App\Services\Storage\S3Storage;
use Knp\Snappy\Pdf;
use Twig\Environment;

class PdfService
{

    private $pdf;
    private $twig;
    /**
     * @var S3Storage
     */
    private $storage;
    private $reportStorage;

    public function __construct(Pdf $pdf, Environment $twig, S3Storage $storage)
    {

        $this->pdf = $pdf;
        $this->twig = $twig;
        $this->storage = $storage;


        $this->pdf->setOptions([
            'page-width' => "1920px",
            'page-height' => "1080px",
            "print-media-type" => true,
            "enable-smart-shrinking" => true,
            "enable-javascript" => true
        ]);
        $this->reportStorage = $_ENV['PUBLIC_REPORT_STORAGE_NAME'];
    }



    public function generatePdf($data = null)
    {
        $report = $data['report'];

        $local = $this->pdf->getTemporaryFolder() . '/' . $report->getLink() . '.pdf';
        $this->pdf->generateFromHtml(
            $this->twig->render(
                'report/report.html.twig',
                ['data' => $data]
            ),
            $local
        );

        $this->storage->uploadPdf($this->reportStorage, $report->getLink() . '.pdf');
        unlink($local);
    }
}
