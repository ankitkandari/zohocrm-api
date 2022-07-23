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
use com\zoho\crm\api\attachments\Attachment;
use com\zoho\crm\api\fields\Field;
use com\zoho\crm\api\record\RecordOperations;
use com\zoho\crm\api\HeaderMap;
use com\zoho\crm\api\layouts\Layout;
use com\zoho\crm\api\ParameterMap;
use com\zoho\crm\api\record\Accounts;
use com\zoho\crm\api\record\ActionWrapper;
use com\zoho\crm\api\record\APIException;
use com\zoho\crm\api\record\ResponseWrapper;
use com\zoho\crm\api\record\BodyWrapper;
use com\zoho\crm\api\record\Comment;
use com\zoho\crm\api\record\Consent;
use com\zoho\crm\api\record\Events;
use com\zoho\crm\api\record\FileDetails;
use com\zoho\crm\api\record\InventoryLineItems;
use com\zoho\crm\api\record\Leads;
use com\zoho\crm\api\record\LineTax;
use com\zoho\crm\api\record\Participants;
use com\zoho\crm\api\record\PricingDetails;
use com\zoho\crm\api\record\Record;
use com\zoho\crm\api\record\SuccessResponse;
use com\zoho\crm\api\relatedrecords\GetRelatedRecordsHeader;
use com\zoho\crm\api\relatedrecords\GetRelatedRecordsParam;
use com\zoho\crm\api\relatedrecords\RelatedRecordsOperations;
use com\zoho\crm\api\tags\Tag;
use com\zoho\crm\api\users\User;
use com\zoho\crm\api\util\Choice;
use Exception;
use Illuminate\Support\Facades\Log;

class ZohoService
{
    private $CLIENT_ID;
    private $CLIENT_SECRET;
    private $GRANT_CODE;
    private $CLIENT_MAIL;
    private $MODULE_NAME;

    public function __construct()
    {
        $this->CLIENT_ID = env('ZOHO_CLIENT_ID');
        $this->CLIENT_SECRET = env('ZOHO_CLIENT_SECRET');
        $this->CLIENT_MAIL = env('ZOHO_CLIENT_MAIL');
        $this->GRANT_CODE = env('ZOHO_GRANT_CODE');
        $this->MODULE_NAME = "Leads";
    }

    private function initialize()
    {
        $logger = Logger::getInstance(Levels::INFO, storage_path() . "/logs/zoho.log");
        $user = new UserSignature($this->CLIENT_MAIL);
        $environment = USDataCenter::PRODUCTION();
        $token = new OAuthToken($this->CLIENT_ID, $this->CLIENT_SECRET, $this->GRANT_CODE, TokenType::GRANT);
        $tokenstore = new FileStore(storage_path() . "/zoho.txt");
        $autoRefreshFields = false;
        $pickListValidation = false;
        $enableSSLVerification = false;
        $connectionTimeout = 20;
        $timeout = 20;
        $sdkConfig = (new SDKConfigBuilder())->setAutoRefreshFields($autoRefreshFields)->setPickListValidation($pickListValidation)->setSSLVerification($enableSSLVerification)->connectionTimeout($connectionTimeout)->timeout($timeout)->build();
        $resourcePath = storage_path();
        Initializer::initialize($user, $environment, $token, $tokenstore, $sdkConfig, $resourcePath, $logger);
    }

    public function getRecords()
    {
        $this->initialize();
        try {
            $recordOperations = new RecordOperations();
            $paramInstance = new ParameterMap();
            $headerInstance = new HeaderMap();

            $response = $recordOperations->getRecords($this->MODULE_NAME, $paramInstance, $headerInstance);

            if ($response != null) {
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
                        return [
                            'success' => true,
                            'data' => $dataArr
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            Log::error($e->message, $e);
            return [
                'success' => false,
                'error_message' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    public function insertRecords($insertItems)
    {
        $this->initialize();
        try {

            $recordOperations = new RecordOperations();

            $bodyWrapper = new BodyWrapper();
            $records = array();
            $recordClass = 'com\zoho\crm\api\record\Record';

            foreach ($insertItems['FirstName'] as $key => $item) :
                $record = new $recordClass();
                $record->addFieldValue(Leads::FirstName(), $insertItems['FirstName'][$key]);
                $record->addFieldValue(Leads::LastName(), $insertItems['LastName'][$key]);
                $record->addFieldValue(Leads::City(), $insertItems['City'][$key]);
                $record->addFieldValue(Leads::State(), $insertItems['State'][$key]);
                $record->addFieldValue(Leads::Company(), $insertItems['Company'][$key]);
                $record->addFieldValue(Accounts::AccountName(), $insertItems['AccountName'][$key]);
                $record->addKeyValue("Subject", $insertItems['Subject'][$key]);
                array_push($records, $record);
            endforeach;

            $bodyWrapper->setData($records);

            $trigger = array("approval", "workflow", "blueprint");
            $bodyWrapper->setTrigger($trigger);

            $response = $recordOperations->createRecords($this->MODULE_NAME, $bodyWrapper);
            if ($response != null) {
                if ($response->isExpected()) {
                    $actionHandler = $response->getObject();
                    if ($actionHandler instanceof ActionWrapper) {

                        $actionWrapper = $actionHandler;
                        $actionResponses = $actionWrapper->getData();
                        $dataArr = [];
                        foreach ($actionResponses as $actionResponse) {
                            $dataItem = [];
                            if ($actionResponse instanceof SuccessResponse) {
                                $successResponse = $actionResponse;
                                foreach ($successResponse->getDetails() as $key => $value) {
                                    $dataItem[$key] = $value;
                                }
                            } else if ($actionResponse instanceof APIException) {
                                $exception = $actionResponse;
                                Log::error($exception->getMessage()->getValue());
                            }
                            array_push($dataArr, $dataItem);
                        }
                        return [
                            'success' => true,
                            'data' => $dataArr
                        ];
                    } else if ($actionHandler instanceof APIException) {
                        $exception = $actionHandler;
                        Log::error($exception->getMessage()->getValue());
                        return [
                            'success' => false,
                            'error_message' => $exception->getMessage()->getValue(),
                            'data' => []
                        ];
                    }
                }
            }
            return [
                'success' => false,
                'error_message' => 'Response data empty',
                'data' => []
            ];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return [
                'success' => false,
                'error_message' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    public function updateRecords($insertItems)
    {
        $this->initialize();
        try {
            $recordOperations = new RecordOperations();
            $request = new BodyWrapper();

            $records = array();
            $recordClass = 'com\zoho\crm\api\record\Record';

            foreach ($insertItems['FirstName'] as $key => $item) :
                $record = new $recordClass();
                $record->setId($insertItems['Id'][$key]);
                $record->addFieldValue(Leads::FirstName(), $insertItems['FirstName'][$key]);
                $record->addFieldValue(Leads::LastName(), $insertItems['LastName'][$key]);
                $record->addFieldValue(Leads::City(), $insertItems['City'][$key]);
                $record->addFieldValue(Leads::State(), $insertItems['State'][$key]);
                $record->addFieldValue(Leads::Company(), $insertItems['Company'][$key]);
                $record->addFieldValue(Accounts::AccountName(), $insertItems['AccountName'][$key]);
                $record->addKeyValue("Subject", $insertItems['Subject'][$key]);
                array_push($records, $record);
            endforeach;

            $request->setData($records);

            $trigger = array("approval", "workflow", "blueprint");
            $request->setTrigger($trigger);

            $response = $recordOperations->updateRecords($this->MODULE_NAME, $request);
            if ($response != null) {
                if ($response->isExpected()) {
                    $actionHandler = $response->getObject();
                    if ($actionHandler instanceof ActionWrapper) {

                        $actionWrapper = $actionHandler;
                        $actionResponses = $actionWrapper->getData();
                        $dataArr = [];
                        foreach ($actionResponses as $actionResponse) {
                            $dataItem = [];
                            if ($actionResponse instanceof SuccessResponse) {
                                $successResponse = $actionResponse;
                                foreach ($successResponse->getDetails() as $key => $value) {
                                    $dataItem[$key] = $value;
                                }
                            } else if ($actionResponse instanceof APIException) {
                                $exception = $actionResponse;
                                Log::error($exception->getMessage()->getValue());
                            }
                            array_push($dataArr, $dataItem);
                        }
                        return [
                            'success' => true,
                            'data' => $dataArr
                        ];
                    } else if ($actionHandler instanceof APIException) {
                        $exception = $actionHandler;
                        Log::error($exception->getMessage()->getValue());
                        return [
                            'success' => false,
                            'error_message' => $exception->getMessage()->getValue(),
                            'data' => []
                        ];
                    }
                }
            }
            return [
                'success' => false,
                'error_message' => 'Response data empty',
                'data' => []
            ];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return [
                'success' => false,
                'error_message' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    public function getRelatedRecords($leadId)
    {
        $this->initialize();
        try {
            $recordId = $leadId;
            $relatedListAPIName = "Products";
            $relatedRecordsOperations = new RelatedRecordsOperations($relatedListAPIName,  $recordId,  $this->MODULE_NAME);

            $paramInstance = new ParameterMap();
            // $paramInstance->add(GetRelatedRecordsParam::page(), 1);
            // $paramInstance->add(GetRelatedRecordsParam::perPage(), 2);
            $headerInstance = new HeaderMap();

            $response = $relatedRecordsOperations->getRelatedRecords($paramInstance, $headerInstance);
            
            if ($response != null) {
                if (in_array($response->getStatusCode(), array(204, 304))) {
                    return [
                        'success' => true,
                        'data' => []
                    ];
                }
                if ($response->isExpected()) {
                    $responseHandler = $response->getObject();

                    if ($responseHandler instanceof \com\zoho\crm\api\relatedrecords\ResponseWrapper) {

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
                            return [
                                'success' => true,
                                'data' => $dataArr
                            ];
                        }
                    } else if ($responseHandler instanceof APIException) {
                        $exception = $responseHandler;
                        Log::error($exception->getMessage()->getValue());
                        return [
                            'success' => false,
                            'error_message' => $exception->getMessage()->getValue(),
                            'data' => []
                        ];
                    }
                }
            }
            dd('here');
            return [
                'success' => false,
                'error_message' => 'Response data empty',
                'data' => []
            ];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return [
                'success' => false,
                'error_message' => $e->getMessage(),
                'data' => []
            ];
        }
    }
}
