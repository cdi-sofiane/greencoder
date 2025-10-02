<?php

namespace App\Helper;

use App\Entity\Encode;

class ReportHelper
{

    const KWH = 0.5; // carbone par kwh par Giga
    const PRICE_KWH = 0.17;

    public function __construct() {}
    /**
     *
     * calcule totalSize
     * calcule totalEmission
     * calcule totalGainCo2 CO2
     */
    public  function preparCalculeOriginal($validReportRow)
    {

        $validReportRow['originalUniteSize'] = $validReportRow['originalSize'];
        $validReportRow['originalSize'] = $this->calculeTotalSize($validReportRow);
        $validReportRow['originalEmissionCo2'] = $this->calculeEmissionCo2($validReportRow); // conso co2  = taile video * co2
        $validReportRow['originalEnergyKwh'] =  $validReportRow['originalSize'] * self::KWH;
        $validReportRow['originalKwhCost'] =  $validReportRow['originalEnergyKwh'] * self::PRICE_KWH; // prix =taile video * prix kwh
        $validReportRow = $this->preparCalculGainTotal($validReportRow);
        return $validReportRow;
    }


    public function calculEmissionCo2($validReportRow)
    {
        return;
    }
    /**
     *
     * calcule encodeTotalSize
     * calcule encodeEmissionCo2
     */
    public  function preparCalculeEncode($validReportEncodeRow)
    {

        $validReportEncodeRow['encodeUniteSize'] = $validReportEncodeRow['encodeSize'];
        $validReportEncodeRow['encodeSize'] = $this->calculeTotalSize($validReportEncodeRow);
        $validReportEncodeRow['encodeEmissionCo2'] = $this->calculeEmissionCo2($validReportEncodeRow);
        $validReportEncodeRow['encodeEnergyKwh'] =  $validReportEncodeRow['encodeSize'] * self::KWH;
        $validReportEncodeRow['encodeKwhCost'] = $this->calculeKwhCost($validReportEncodeRow);


        return $validReportEncodeRow;
    }

    public function preparCalculGainTotal($validReportRow)
    {

        $validReportRow['GainCo2'] = $this->calculeGainCo2($validReportRow);
        $validReportRow['GainEnergyKwh'] = $this->calculeGainEnergyKwh($validReportRow);
        $validReportRow['GainKwhCost'] = $this->calculeGainEnergyKwh($validReportRow) * self::PRICE_KWH;

        return $validReportRow;
    }



    // (0,5 kwh * totalSize * GainReal) /100
    public  function  calculeGainEnergyKwh($validReportRow)
    {

        return (self::KWH * $this->targetTotalSize($validReportRow) * $validReportRow['realGain']) / 100;
    }
    /**
     * calcule le cumule des video en fonction du taux de completion et du nombre de vue
     */
    private function  calculeTotalSize($validReportRow)
    {

        return  $this->targetSize($validReportRow)  * ($validReportRow['totalCompletion'] / 100) * $validReportRow['totalViews'];
    }

    private function  calculeEmissionCo2($validReportRow)
    {
        return  $this->targetTotalSize($validReportRow) * (($validReportRow['mobileCarbonWeight'] * ($validReportRow['mobileRepartition'] / 100)) + ($validReportRow['desktopCarbonWeight'] * ($validReportRow['desktopRepartition'] / 100))) / 1000;
    }


    private function calculeGainCo2($validReportRow)
    {

        return ($this->targetGainCo2($validReportRow) * ($validReportRow['realGain'])) / 100;
    }

    private function calculeKwhCost($validReportRow)
    {

        return $this->targetEnergy($validReportRow) * self::PRICE_KWH;
    }
    /**
     * with video sizer return for encode or original its size
     */
    public  function targetSize($validReportRow)
    {
        $targetSize = isset($validReportRow['originalSize']) ? $validReportRow['originalSize'] : $validReportRow['encodeSize'];

        return $targetSize;
    }

    /**
     * with video sizer return for encode or original its size
     * @return float
     */
    public  function targetGainCo2($validReportRow)
    {
        $targetGainCO2 = isset($validReportRow['originalEmissionCo2']) ? $validReportRow['originalEmissionCo2'] : $validReportRow['encodeEmissionCo2'];

        return $targetGainCO2;
    }
    /**
     * with video sizer return for encode or original its size
     * @return float
     */
    public  function targetTotalSize($validReportRow)
    {
        $targetTotalSize = isset($validReportRow['originalSize']) ? $validReportRow['originalSize'] : $validReportRow['encodeSize'];

        return $targetTotalSize;
    }
    /**
     * with video sizer return for encode or original its size
     */
    public  function targetEnergy($validReportRow)
    {
        $targetSize = isset($validReportRow['originalEnergyKwh']) ? $validReportRow['originalEnergyKwh'] : $validReportRow['encodeEnergyKwh'];

        return $targetSize;
    }
    public  function targetCost($validReportRow)
    {
        $targetSize = isset($validReportRow['originalKwhCost']) ? $validReportRow['originalKwhCost'] : $validReportRow['encodeKwhCost'];

        return $targetSize;
    }

    /**
     * calcule l'optimization reel d'une video encoder selectioner par raport a l'original
     */
    public  function calculeRealGain($targetEncode)
    {
        $videoOptimisation = $targetEncode->getSize() != 0 ? 100 - (($targetEncode->getSize() / $targetEncode->getVideo()->getSize()) * 100) : 0;
        return round($videoOptimisation, 2, PHP_ROUND_HALF_EVEN);
    }
    /**
     * calcule l'optimization reel du total video encoder selectioner par raport a total original
     */
    static  function calculeTotalRealGain($dataReport)
    {
        $videoOptimisation = round(100 * (1 - ($dataReport['totalEncodedSize'] / $dataReport['totalOriginalSize'])), 0, PHP_ROUND_HALF_UP);
        return $videoOptimisation;
    }
    /**
     * convert gramme to kilogram
     *
     * @param integer $value
     * @return float
     */
    public  function ConvertGrammeToKilogramme($value)
    {

        $result = $value == 0 ? 0 : $value / 1000;
        return $result;
    }

    /**
     * convert gramme to kilogram
     *
     * @param integer $value
     * @return int
     */
    public  function ConvertGrammeToTonne($value)
    {

        $result = $value == 0 ? 0 : $value / 1_000_000;
        return $result;
    }

    /**
     * convert bytes to MegaOctets
     *
     * @param integer $value
     * @return float
     */
    public  function ConvertBytesToMegaBytes($value)
    {

        $result = $value == 0 ? 0 : $value / 1_000_000;
        return $result;
    }

    /** convert bytes to MegaOctets
     *
     * @param integer $value
     * @return float
     */
    public  function ConvertBytesToGigaBytes($value)
    {

        $result = $value == 0 ? 0 : $value / 1_000_000_000;
        return  $result;
    }

    static public function pourcentageForGainEncodage($nbrVideoWithGain, $totalVideo)
    {
        return ($nbrVideoWithGain / $totalVideo) * 100;
    }

    static public function roundify($number)
    {
        return round($number, 0, PHP_ROUND_HALF_UP);
    }

    static public function baseSizeConverter($value)
    {

        if (($value < 1)) {
            return round($value * 1_000, 2) . ' Mo';
        } elseif ((1 <= $value) && ($value < 1_000)) {

            return round($value, 2)  . ' Go';
        } elseif ((1_000 <= $value) && ($value < 1_000_000)) {

            return round($value / 1_000, 2) . ' To';
        } elseif (1_000_000 <= $value) {

            return round($value / 1_000_000, 2) . ' Po';
        }
    }
    static public function baseCarbonConverter($value)
    {

        if (($value < 1)) {
            return round($value * 1_000, 2) . ' Gr';
        } elseif ((1 <= $value) && ($value < 1_000)) {

            return round($value, 2)  . ' kg';
        } elseif ((1_000 <= $value)) {

            return round($value / 1_000, 2) . ' Tn';
        }
    }

    static public function basePuissanceConverter($value)
    {

        if (($value < 1)) {
            return round($value * 1_000, 2) . ' Wh';
        } elseif ((1 <= $value) && ($value < 1_000)) {

            return round($value, 2)  . ' kWh';
        } elseif ((1_000 <= $value) && ($value < 1_000_000)) {

            return round($value / 1_000, 2) . ' MWh';
        } elseif (1_000_000 <= $value) {

            return round($value / 1_000_000, 2) . ' GWh';
        }
    }
}
