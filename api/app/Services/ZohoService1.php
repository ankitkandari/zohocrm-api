<?php

namespace App\Services;

use com\zoho\api\authenticator\OAuthToken;
use com\zoho\api\authenticator\TokenType;
use com\zoho\api\authenticator\store\FileStore;
use com\zoho\crm\api\Initializer;
use com\zoho\crm\api\UserSignature;
use com\zoho\crm\api\SDKConfigBuilder;
use com\zoho\crm\api\dc\USDataCenter;
use com\zoho\api\logger\Logger;
use com\zoho\api\logger\Levels;
use com\zoho\crm\api\record\RecordOperations;
use com\zoho\crm\api\HeaderMap;
use com\zoho\crm\api\ParameterMap;
use com\zoho\crm\api\record\ResponseWrapper;
use Illuminate\Support\Facades\Log;

class ZohoService1
{
    public function getRecord()
    {
        $logger = Logger::getInstance(Levels::INFO, storage_path() . "/logs/zoho.log");
        $user = new UserSignature("codertacina@gmail.com");
        $environment = USDataCenter::PRODUCTION();
        $token = new OAuthToken("1000.6I5VSC8CJT34K4AXFXYX1K0YORV3BV", "8e30e66a4235bb5b2f34b35905e1e8780c12d9f790", "1000.018abd66a9e947bc69946707c89f8ff1.1113ae1be7e72b7a2d30c6eaeb184bd5", TokenType::GRANT);
        $tokenstore = new FileStore(storage_path() . "/zoho.txt");
        $autoRefreshFields = false;
        $pickListValidation = false;
        $enableSSLVerification = false;
        $connectionTimeout = 20;
        $timeout = 20;
        $sdkConfig = (new SDKConfigBuilder())->setAutoRefreshFields($autoRefreshFields)->setPickListValidation($pickListValidation)->setSSLVerification($enableSSLVerification)->connectionTimeout($connectionTimeout)->timeout($timeout)->build();
        $resourcePath = storage_path();
        Initializer::initialize($user, $environment, $token, $tokenstore, $sdkConfig, $resourcePath, $logger);

        try {
            $recordOperations = new RecordOperations();
            $paramInstance = new ParameterMap();
            $headerInstance = new HeaderMap();
            $moduleAPIName = "Leads";
            $response = $recordOperations->getRecords($moduleAPIName, $paramInstance, $headerInstance);

            if ($response != null) {
                // echo ("Status Code: " . $response->getStatusCode() . "\n");
                $responseHandler = $response->getObject();

                if ($responseHandler instanceof ResponseWrapper) {
                    $responseWrapper = $responseHandler;
                    $records = $responseWrapper->getData();
                    if ($records != null) {
                        $dataArr = [];
                        foreach ($records as $record) {
                            $dataItem = [];
                            foreach ($record->getKeyValues() as $keyName => $value) {
                                $dataItem[$keyName] = $value;
                            }
                            array_push($dataArr, $dataItem);
                        }
                        return $dataArr;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error($e->message, $e);
            return [];
        }
    }
}
