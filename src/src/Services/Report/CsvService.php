<?php

namespace App\Services\Report;

use App\Services\Storage\S3Storage;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CsvService
{
    private $storage;
    private $parameterBag;
    public function __construct(S3Storage $storage, ParameterBagInterface $parameterBag)
    {

        $this->storage = $storage;
        $this->parameterBag = $parameterBag;
    }

    public function generateCsv($datas, $reportPages)
    {
        $data = self::orderCsvColumn($datas);
        $report = $reportPages['report'];
        $useKeysForHeaderRow = true;

        if ($useKeysForHeaderRow) {
            array_unshift($data, array_keys(reset($data)));
        }

        $outputBuffer = fopen($this->parameterBag->get('video_directory') . '/' . $report->getLink() . '.csv', 'wb');

        $data[0] = self::transformNameCsv();

        foreach ($data as $v) {

            fputcsv($outputBuffer, $v);
        }

        fclose($outputBuffer);

        $this->storage->uploadCsv($report);
        unlink($this->parameterBag->get('video_directory')  . '/' . $report->getLink() . '.csv');
    }

    public function csvToArray($report)
    {
        $fileIdentifier = $report->getLink() . '.csv';
        $string = $this->storage->getReportFile($fileIdentifier);
        $string = trim($string, "^ \"\n");

        $rows = str_getcsv($string, "\n");
        $data = array_map('str_getcsv', $rows);

        $headers =  array_shift($data);
        $csv = [];
        foreach ($data as $row) {
            $csv[] = array_combine($headers, $row);
        }


        $data = array_map(function ($rows) {
            $firstcol['uuid'] =  $rows['Uuid'];
            $firstcol['totalCompletion'] =  $rows['Taux de complétion (%)'];
            $firstcol['mobileRepartition'] =  $rows['Répartition mobile (%)'];
            $firstcol['desktopRepartition'] =  $rows['Répartition poste fixe (%)'];
            $firstcol['mobileCarbonWeight'] =  isset($rows['Poids carbone - mobile (gr)']) ? $rows['Poids carbone - mobile (gr)'] : $rows['Mobile poids carbone (gr)'];
            $firstcol['desktopCarbonWeight'] =  isset($rows['Poids carbone - poste fixe(gr)']) ? $rows['Poids carbone - poste fixe(gr)'] : $rows['Poste fixe poids carbone (gr)'];
            $firstcol['resolution'] =  $rows['Résolution'];
            $firstcol['totalViews'] = $rows['Nombre d\'impression(s)'];
            return $firstcol;
        }, $csv);
        return $data;
    }

    private static function transformNameCsv()
    {
        return  $arrNames = [
            0 => "Uuid",
            1 => "Taux de complétion (%)",
            2 => "Répartition mobile (%)",
            3 => "Répartition poste fixe (%)",
            4 => "Poids carbone - mobile (gr)",
            5 => "Poids carbone - poste fixe(gr)",
            6 => "Nom de la vidéo",
            7 => "Résolution",
            8 => "Nombre d'impression(s)",
            9 => "Poids unitaire - source (Go)",
            10 => "Total poids - source (Go)",
            11 => "Émission CO2 - source (kg)",
            12 => "Énergie élec. - source (kWh)",
            13 => "Coût - source (€)",
            14 => "Poids unitaire - GreenEncodée (Go)",
            15 => "Total poids - GreenEncodée (Go)",
            16 => "Emission Co2 - GreenEncodée (Kg)",
            17 => "Energie élec. - GreenEncodée (kWh)",
            18 => "Coût - GreenEncodée (€)",
            19 => "Gain carbone (Kg)",
            20 => "Gain énergie (kWh)",
            21 => "Économies réalisées (€)",
            22 => "Réduction poids (%)",
        ];
    }
    private static function orderCsvColumn($datas)
    {
        return array_map(function ($item) {
            return [
                "uuid" => $item["uuid"],
                "totalCompletion" => $item["totalCompletion"],
                "mobileRepartition" => $item["mobileRepartition"],
                "desktopRepartition" => $item["desktopRepartition"],
                "mobileCarbonWeight" => $item["mobileCarbonWeight"],
                "desktopCarbonWeight" => $item["desktopCarbonWeight"],
                "name" => $item["name"],
                "resolution" => $item["resolution"],
                "totalViews" => $item["totalViews"],
                "originalUniteSize" => $item["originalUniteSize"],
                "originalSize" => $item["originalSize"],
                "originalEmissionCo2" => $item["originalEmissionCo2"],
                "originalEnergyKwh" => $item["originalEnergyKwh"],
                "originalKwhCost" => $item["originalKwhCost"],
                "encodeUniteSize" => $item["encodeUniteSize"],
                "encodeSize" => $item["encodeSize"],
                "encodeEmissionCo2" => $item["encodeEmissionCo2"],
                "encodeEnergyKwh" => $item["encodeEnergyKwh"],
                "encodeKwhCost" => $item["encodeKwhCost"],
                "GainCo2" => $item["GainCo2"],
                "GainEnergyKwh" => $item["GainEnergyKwh"],
                "GainKwhCost" => $item["GainKwhCost"],
                "realGain" => $item["realGain"],
            ];
        }, $datas);
    }
}
