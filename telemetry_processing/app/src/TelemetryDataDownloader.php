<?php
/**
 * TelemetryDataDownloader.php
 *
 * Class used to download data from M2M server
 *
 * @author Adam Machowczyk, Dominik Michalski
 */

namespace telemetryProcessing;

class TelemetryDataDownloader
{
    private $username;
    private $password;
    private $count;
    private $deviceMsisdn;
    private $countryCode;
    private $operationRequired;
    private $result_attribute;
    private $soap_call_parameters;
    private $result;

    public function __construct()
    {
    }

    public function __destruct()
    {
    }

    /**
     * Function prepares parameters to be parsed
     */
    public function setParserParameters($parser_parameters)
    {
        $this->username = $parser_parameters['username'];
        $this->password = $parser_parameters['password'];
        $this->count = $parser_parameters['count'];
        $this->deviceMsisdn = $parser_parameters['deviceMsisdn'];
        $this->countryCode = $parser_parameters['countryCode'];
        $this->operationRequired = $parser_parameters['connectionType'];
    }
    
    /**
     * Function parses telemetry data downloaded from M2M server using a SOAP client
     */
    public function parseTelemetryData()
    {
        $result = null;
        $soap_client_handle = null;
        $soap_function = $this->selectOperation(); //such as read data, insert data, generate report

        $soap_client_handle = $this->createSoapClient();

        //only proceed if operation was specified and so was SOAP client
        if ($soap_client_handle !== false && $soap_function != 'null') {
            $result = $this->parseData($soap_client_handle, $soap_function);
        }

        $this->result = $result;
    }
    
    /**
     * Select an operation to be performed by SOAP client
     *
     * @return string
     */
    private function selectOperation()
    {
        $soap_function = '';
        $soap_call_parameters = [];
        $result_attribute = '';

        $operation_required = $this->operationRequired;
        switch ($operation_required) {
            case 'peekMessages':
                $soap_function = 'peekMessages';
                $soap_call_parameters = [
                    'username' => $this->username,
                    'password' => $this->password,
                    'count' => $this->count,
                    'deviceMsisdn' => $this->deviceMsisdn,
                    'countryCode' => $this->countryCode
                ];
                $result_attribute = 'peekMessagesResponse';
                break;
        }
        $this->result_attribute = $result_attribute;
        $this->soap_call_parameters = $soap_call_parameters;

        return $soap_function;
    }
    
    /**
     * Create a SOAP client using settings and wsdl file
     *
     * @return mixed
     */
    private function createSoapClient()
    {
        $soap_client_handle = false;

        $soapclient_attributes = ['trace' => true, 'exceptions' => true];
        $wsdl = WSDL;

        try {
            $soap_client_handle = new \SoapClient($wsdl, $soapclient_attributes);
        } catch (\SoapFault $exception) {
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
    private function parseData($soap_client_handle, $soap_function)
    {
        $result = null;

        try {
            $conversion_result = $soap_client_handle->__soapCall($soap_function, $this->soap_call_parameters);
            $result = $conversion_result;
        } catch (\SoapFault $exception) {
            trigger_error($exception);
        }

        return $result;
    }

    public function getResult()
    {
        return $this->result;
    }
}
