<?php
/**
 * Created by PhpStorm.
 * User: slim
 * Date: 24/10/17
 * Time: 10:01
 */

namespace TempConv;

class TemperatureConversionModel
{
    private $temperature;
    private $calculation_type;
    private $windspeed;
    private $result_attribute;
    private $soap_call_parameters;
    private $result;

    public function __construct(){}

    public function __destruct(){}

    public function setConversionParameters($conversion_parameters)
    {
        $this->calculation_type = $conversion_parameters['cleaned_calculation'];
        $this->temperature = $conversion_parameters['cleaned_temperature'];
        $this->windspeed = $conversion_parameters['cleaned_windspeed'];
    }

    public function performTemperatureConversion()
    {
        $result = null;
        $soap_client_handle = null;
        $soap_function = $this->selectCalculation();

        $soap_client_handle = $this->createSoapClient();

        if ($soap_client_handle !== false && $soap_function != 'null')
        {
            $result = $this->convertTemperature($soap_client_handle, $soap_function);
        }

        $this->result = $result;
    }

    /**
     * Each type of calculation has a separate function call with differently named parameters
     *
     * @return string
     */
    private function selectCalculation()
    {
        $soap_function = '';
        $soap_call_parameters = [];
        $result_attribute = '';

        $conversion_required = $this->calculation_type;
        switch($conversion_required)
        {
            case 'ctof':
                $soap_function = 'CelsiusToFahrenheit';
                $soap_call_parameters = [
                    'nCelsius' => $this->temperature
                ];
                $result_attribute = 'CelsiusToFahrenheitResult';
                break;
            case 'ftoc':
                $soap_function = 'FahrenheitToCelsius';
                $soap_call_parameters = [
                    'nFahrenheit' => $this->temperature
                ];
                $result_attribute = 'FahrenheitToCelsiusResult';
                break;
            case 'cchill':
                $soap_function = 'WindChillInCelsius';
                $soap_call_parameters = [
                    'nCelsius' => $this->temperature,
                    'nWindSpeed' => $this->windspeed
                ];
                $result_attribute = 'WindChillInCelsiusResult';
                break;
            case 'fchill':
                $soap_function = 'WindChillInFahrenheit';
                $soap_call_parameters = [
                    'nFahrenheit' => $this->temperature,
                    'nWindSpeed' => $this->windspeed
                    ];
                $result_attribute = 'WindChillInFahrenheitResult';
                break;
        }
        $this->result_attribute = $result_attribute;
        $this->soap_call_parameters = $soap_call_parameters;

        return $soap_function;
    }

    private function createSoapClient()
    {
        $soap_client_handle = false;

        $soapclient_attributes = ['trace' => true, 'exceptions' => true];
        $wsdl = WSDL;

        try
        {
            $soap_client_handle = new \SoapClient($wsdl, $soapclient_attributes);
//            var_dump($soap_client_handle->__getFunctions());
//            var_dump($soap_client_handle->__getTypes());
        }
        catch (\SoapFault $exception)
        {
            trigger_error($exception);
        }

        return $soap_client_handle;
    }

    /**
     * Note the use of the vairaible variable to extract the appropriate returned attribute
     *
     * @param $soap_client_handle
     * @param $soap_function
     * @return bool|null
     */
    private function convertTemperature($soap_client_handle, $soap_function)
    {
        $result = null;

        try
        {
            $conversion_result = $soap_client_handle->__soapCall($soap_function, [$this->soap_call_parameters]);
            $result_attribute = $this->result_attribute;
            $result = $conversion_result->$result_attribute;
//      var_dump($obj_soap_client_handle->__getLastRequest());
//      var_dump($obj_soap_client_handle->__getLastResponse());
//      var_dump($obj_soap_client_handle->__getLastRequestHeaders());
//      var_dump($obj_soap_client_handle->__getLastResponseHeaders());
        }
        catch (\SoapFault $exception)
        {
            trigger_error($exception);
        }

        return $result;
    }

    public function getResult()
    {
        return $this->result;
    }

}