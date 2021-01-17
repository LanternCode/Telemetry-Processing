<?php
/**
 * TelemetryDataParser.php
 *
 * class used to parse data received from M2M server
 *
 * @author Adam Machowczyk, Dominik Michalski
 */

namespace telemetryProcessing;

class TelemetryDataParser
{
    private $dataString;
    private $switchesArray;
    private $fan;
    private $temperature;
    private $keypad;
    private $id;
    private $srcNumber;

    private $result;

    public function __construct()
    {
    }

    public function __destruct()
    {
    }

    /**
     * Function prepares parameters to be parsed
     *
     * @param $parser_parameters
     */
    public function setParserParameters($parser_parameters)
    {
        $this->dataString = $parser_parameters['dataString'];
        $this->switchesArray = $parser_parameters['switchesArray'];
        $this->fan = $parser_parameters['fan'];
        $this->temperature = $parser_parameters['temperature'];
        $this->keypad = $parser_parameters['keypad'];
        $this->id = $parser_parameters['unique_id'];
        $this->srcNumber = $parser_parameters['src_number'];
    }

    /**
     *
     * Function parses telemetry data
     *
     */
    public function parseTelemetryData()
    {
        $result = [];

        $result['switches'] = $this->switchesArray;
        $result['fan'] = $this->fan;
        $result['temperature'] = $this->temperature;
        $result['keypad'] = $this->keypad;
        $result['unique_id'] = $this->id;
        $result['src_number'] = $this->srcNumber;

        $this->result = $result;
    }

    public function getResult()
    {
        return $this->result;
    }
}
