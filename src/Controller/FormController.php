<?php

declare(strict_types=1);

namespace App\Controller;

use App\Validation\FormValidation;
use App\Helper\ControllerHelper;
use App\Controller\WhitepaperController;
use Strata\Frontend\Cms\RestData;
use Strata\Frontend\ContentModel\ContentModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Psr16Cache;
use Aws\S3\S3Client;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Form controller
 */
class FormController extends AbstractController
{
    /**
     * Frameworks Rest API data
     *
     * @var RestData
     */
    protected $api;

    protected $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        $this->api = new RestData(
            getenv('APP_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
        );
        $this->api->setContentType('esourcing_dates');
        $psr16Cache = new Psr16Cache($cache);
        $this->api->setCache($psr16Cache);

        $this->client = HttpClient::create();
        $this->logger = $logger;
    }

    public function esourcingRegisterSubmit(Request $request)
    {
        $params = $request->request;

        ControllerHelper::honeyPot($params->get('surname', null));

        $formData = [
            'name'      => $params->get('name', null),
            'jobTitle'  => $params->get('jobTitle'),
            'email'     => $params->get('email', null),
            'phone'     => $params->get('phone', null),
            'company'   => $params->get('company', null),
        ];


        $formErrors = $this->validateEsourcingRegister($formData);

        if ($formErrors) {
            return $this->render('forms/29-esourcing-register.html.twig', [
                'formErrors' => $formErrors,
                'formData' => $formData,
            ]);
        } else {
            $params->set('subject', 'Website - eSourcing Access');
            $params->set('recordType', '012b00000005NWC');
            $params->set('00Nb0000009IXEs', $formData['jobTitle']);
            $params->set('priority', 'Green');
            $params->set('origin', 'Website - eSourcing Access');
            $params->set('orgid', ControllerHelper::getOrgId());

            $response = $this->client->request('POST', getenv('SALESFORCE_WEB_TO_CASE_URL'), [
                'query'             => $params->all(),
            ]);

            if (!is_null($params->get('debug'))) {
                return new Response(htmlspecialchars($response->getContent()));
            }
        }

        return $this->redirectToRoute('form_esourcing_register_thanks');
    }

    /**
     * eSourcingTraining Form template
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Strata\Frontend\Exception\ContentFieldException
     * @throws \Strata\Frontend\Exception\ContentFieldNotSetException
     * @throws \Strata\Frontend\Exception\ContentTypeNotSetException
     * @throws \Strata\Frontend\Exception\FailedRequestException
     * @throws \Strata\Frontend\Exception\PermissionException
     */
    public function eSourcingTraining(Request $request)
    {
        $this->api->setContentType('esourcing_dates');
        $this->api->setCacheKey($request->getRequestUri());

        // @todo At present need to pass fake ID since API method is intended to return one item with an ID, review this
        $results = $this->api->getOne(0);

        $data = ['esourcingDates' => $results];

        return $this->render('forms/31-esourcing-training.html.twig', $data);
    }

    public function esourcingTrainingSubmit(Request $request)
    {
        $params = $request->request;

        ControllerHelper::honeyPot($params->get('surname', null));

        $formData = [
            "customerType"  => $params-> get('customer-type', null),
            "buyerDate"     => $params-> get('buyer-training-dates', null),
            "supplierDate"  => $params-> get('supplier-training-dates', null),
            'name'          => $params->get('name', null),
            'jobTitle'      => $params->get('jobTitle'),
            'email'         => $params->get('email', null),
            'phone'         => $params->get('phone', null),
            'company'       => $params->get('company', null),
        ];

        $formErrors = $this->validateEsourcingTraining($formData);

        if ($formErrors) {
            $this->api->setCacheKey($request->getRequestUri());
            $results = $this->api->getOne(0);

            return $this->render('forms/31-esourcing-training.html.twig', [
                'formErrors'    => $formErrors,
                'formData'      => $formData,
                'esourcingDates' => $results
            ]);
        } else {
            $params->set('subject', 'Website - eSourcing Training');
            $params->set('recordType', '012b00000005NWC');
            $params->set('00Nb0000009IXEs', $formData['jobTitle']);
            $params->set('priority', 'Green');
            $params->set('origin', 'Website - eSourcing Training');
            $params->set('orgid', ControllerHelper::getOrgId());

            $response = $this->client->request('POST', getenv('SALESFORCE_WEB_TO_CASE_URL'), [
                'query'         => $params->all(),
            ]);

            if (!is_null($params->get('debug'))) {
                return new Response(htmlspecialchars($response->getContent()));
            }
        }

        return $this->redirectToRoute('form_esourcing_training_thanks');
    }

    public function contactCCS(Request $request)
    {
        $referrer = $_SERVER['HTTP_REFERER'] ?? null;
        $formType = $request->query->get('type', null);


        $cscMessage = ControllerHelper::getCSCMessage();

        $data = [
            'referrer'      => $referrer,
            'rmNumber'      => $referrer != null ? ControllerHelper::extractRmNumberFromReferrer($referrer) : null,
            'cscMessage'    => $cscMessage,
            'formType'      => $formType,
        ];

        return $this->render('forms/22-contact.html.twig', $data);
    }

    public function contactCcsSubmit(Request $request)
    {
        $params = $request->request;

        ControllerHelper::honeyPot($params->get('surname', null));

        $formData = [
            'enquiryType'           => $params->get('origin', null),
            'name'                  => $params->get('name', null),
            'email'                 => $params->get('email', null),
            'phone'                 => $params->get('phone', null),
            'company'               => $params->get('company', null),
            'jobTitle'              => $params->get('jobTitle', null),
            'moreDetail'            => $params->get('more-detail', null),
            'callback'              => $params->get('00Nb0000009IXEg', null),
            'contactedBefore'       => $params->get('contactedBefore', null),
            'caseNumber'            => $params->get('00N4L000009vOyr', null),
            'callbackTimeslot'      => $params->get('callbackTimeslot', null),
            'callbackTimeslotForC'  => $params->get('callbackTimeslotForC', null),
            'customerType'          => $params->get('customerType', null),
            'contactWay'            => $params->get('contactWay', null),
        ];

        if (!empty($formData)) {
            $formErrors = $this->validateContactCCS($formData);

            if ($formErrors) {
                $cscMessage = ControllerHelper::getCSCMessage();
                return $this->render('forms/22-contact.html.twig', [
                    'referrer'              => $params->get('00N4L000009OPAj', null),
                    'formErrors'            => $formErrors,
                    'formData'              => $formData,
                    'cscMessage'            => $cscMessage,
                    'fileAttachedBefore'    => !(empty($formErrors["fileErr"]["errors"])) ? false : $this->fileAttached(),
                ]);
            } else {
                $params->set('subject', 'Contact GCA');
                $params->set('00Nb0000009IXEW', 'General-Enquiry');
                $params->set(getenv('SF_RM_CASE_ID'), ControllerHelper::extractRmNumberFromReferrer($params->get('00N4L000009OPAj', null)));
                $params->set('recordType', '012b00000005NWC');
                $params->set('00Nb0000009IXEs', $formData['jobTitle']);
                $params->set('priority', 'Green');
                $params->set('orgid', ControllerHelper::getOrgId());

                if ($formData['callbackTimeslot'] != null) {
                    $params->set('Call_Back_Preference__c', $formData['callbackTimeslot']);
                } elseif ($formData['callbackTimeslotForC'] != null) {
                    $params->set('Call_Back_Preference__c', $formData['callbackTimeslotForC']);
                }

                $attachmentID_filename = $this->sendToDocumentUpload();

                if ($attachmentID_filename != null) {
                    $params->set('00N4L000009vP2P', getenv('documentHanding_path') . $attachmentID_filename);
                }

                $response = $this->client->request('POST', getenv('SALESFORCE_WEB_TO_CASE_URL'), [
                    'query' => $params->all(),
                ]);

                if (!is_null($params->get('debug'))) {
                    return new Response(
                        htmlspecialchars($response->getContent())
                    );
                } else {
                    return $this->redirectToRoute('form_contact_thanks');
                }
            }
        }
    }

    public function complaintForm(Request $request)
    {
        $referrer = $_SERVER['HTTP_REFERER'] ?? null;
        $formType = $request->query->get('type', null);
        $rmNumber = $request->query->get('agreement', null);


        $cscMessage = ControllerHelper::getCSCMessage();

        $data = [
            'referrer'      => $referrer,
            'rmNumber'      => $rmNumber,
            'cscMessage'    => $cscMessage,
            'formType'      => $formType,
        ];

        return $this->render('forms/complaint_form.html.twig', $data);
    }

    public function complaintFormSubmit(Request $request)
    {
        $params = $request->request;

        ControllerHelper::honeyPot($params->get('surname', null));

        $formData = [
            'enquiryType'           => $params->get('origin', null),
            'name'                  => $params->get('name', null),
            'email'                 => $params->get('email', null),
            'phone'                 => $params->get('phone', null),
            'company'               => $params->get('company', null),
            'jobTitle'              => $params->get('jobTitle', null),
            'moreDetail'            => $params->get('more-detail', null),
            'callback'              => $params->get('00Nb0000009IXEg', null),
            'contactedBefore'       => $params->get('contactedBefore', null),
            'caseNumber'            => $params->get('00N4L000009vOyr', null),
            'callbackTimeslot'      => $params->get('callbackTimeslot', null),
            'callbackTimeslotForC'  => $params->get('callbackTimeslotForC', null),
            'customerType'          => $params->get('customerType', null),
            'contactWay'            => $params->get('contactWay', null),
        ];

        if (!empty($formData)) {
            $formErrors = $this->validateContactCCS($formData);

            if ($formErrors) {
                $cscMessage = ControllerHelper::getCSCMessage();
                return $this->render('forms/complaint_form.html.twig', [
                    'referrer'              => $params->get('00N4L000009OPAj', null),
                    'rmNumber'              => $params->get('00NS90000025xmH', null),
                    'formErrors'            => $formErrors,
                    'formData'              => $formData,
                    'cscMessage'            => $cscMessage,
                    'fileAttachedBefore'    => !(empty($formErrors["fileErr"]["errors"])) ? false : $this->fileAttached(),
                ]);
            } else {
                $params->set('subject', 'Contact GCA');
                $params->set('00Nb0000009IXEW', 'General-Enquiry');
                $params->set(getenv('SF_RM_CASE_ID'), $params->get('00NS90000025xmH', null));
                $params->set('recordType', '012b00000005NWC');
                $params->set('00Nb0000009IXEs', $formData['jobTitle']);
                $params->set('priority', 'Green');
                $params->set('orgid', ControllerHelper::getOrgId());

                if ($formData['callbackTimeslot'] != null) {
                    $params->set('Call_Back_Preference__c', $formData['callbackTimeslot']);
                } elseif ($formData['callbackTimeslotForC'] != null) {
                    $params->set('Call_Back_Preference__c', $formData['callbackTimeslotForC']);
                }

                $attachmentID_filename = $this->sendToDocumentUpload();

                if ($attachmentID_filename != null) {
                    $params->set('00N4L000009vP2P', getenv('documentHanding_path') . $attachmentID_filename);
                }

                $response = $this->client->request('POST', getenv('SALESFORCE_WEB_TO_CASE_URL'), [
                    'query' => $params->all(),
                ]);

                if (!is_null($params->get('debug'))) {
                    return new Response(
                        htmlspecialchars($response->getContent())
                    );
                } else {
                    return $this->redirectToRoute('form_contact_thanks_complaint');
                }
            }
        }
    }


    public function newsletters(Request $request)
    {
        $params = $request->request;

        ControllerHelper::honeyPot($params->get('surname', null));

        $formData = [
            'name'          => $params->get('name', null),
            'email'         => $params->get('email', null),
            'company'       => $params->get('company', null),
            'jobTitle'      => $params->get('jobTitle', null),
        ];

        if (!empty($formData)) {
            $formErrors = $this->validateNewsletterForm($formData);

            if ($formErrors) {
                return $this->render('forms/27-newsletter.html.twig', [
                    'formErrors' => $formErrors,
                    'formData' => $formData,
                ]);
            } else {
                $params->set('subject', 'Newsletter');
                $params->set('00Nb0000009IXEW', 'Newsletter');
                $params->set('recordType', '012b00000005NWC');
                $params->set('priority', 'Green');
                $params->set('orgid', ControllerHelper::getOrgId());
                $params->set('origin', 'Website - Newsletter');

                $response = $this->client->request('POST', getenv('SALESFORCE_WEB_TO_CASE_URL'), [
                    'query' => $params->all(),
                ]);

                if (!is_null($params->get('debug'))) {
                    return new Response(
                        htmlspecialchars($response->getContent())
                    );
                } else {
                    return $this->redirectToRoute('form_newsletter_thanks');
                }
            }
        }
    }
    public function sendToSalesforceForDownload($params, $utmParams, $data, $campaignCode, $description)
    {
        $formErrors = $this->validateGatedForm($data);

        if (!$formErrors) {
            $client = HttpClient::create();

            $params = self::checkUTM($utmParams, $params);

            $params->set('subject', $campaignCode);
            $params->set('00Nb0000009IXEW', $campaignCode);
            $params->set('00Nb0000009IXEn', '1');
            $params->set('recordType', '012b00000005NWC');
            $params->set('priority', 'Green');
            $params->set('description', $description);
            $params->set('orgid', ControllerHelper::getOrgId());
            $params->set('origin', 'Website - Download');

            $client->request('POST', getenv('SALESFORCE_WEB_TO_CASE_URL'), [
                  'query' => $params->all(),
              ]);
        }

        return $formErrors;
    }

    public function validateContactCCS(array $data)
    {
        $errorMessages = [];

        $errorMessages['nameErr'] =         FormValidation::validationName($data['name']);
        $errorMessages['emailErr'] =        FormValidation::validationEmail($data['email']);
        $errorMessages['jobTitleErr'] =     FormValidation::validationJobTitleForContactCCS($data['jobTitle']);
        $errorMessages['companyErr'] =      FormValidation::validationCompany($data['company']);

        if ($data['enquiryType'] == "Website - Complaint") {
            $errorMessages['phoneErr'] = FormValidation::validationPhone($data['phone']);
            $errorMessages['customerTypeErr'] = FormValidation::validationCustomerType($data['customerType']);
            $errorMessages['contactWayErr'] = FormValidation::validationContactWay($data['contactWay']);
        } elseif (!($data['callback'] == "No" || $data['callback'] == null)) {
            $errorMessages['phoneErr'] = FormValidation::validationPhone($data['phone']);
        }

        $errorMessages['moreDetailErr'] =   FormValidation::validationMoreDetail($data['moreDetail']);

        if ($this->fileAttached()) {
            $errorMessages['fileErr'] = FormValidation::validationFile($_FILES['attachment']);
        }

        return $this->formatErrorMessages($errorMessages);
    }

    private function validateNewsletterForm($data)
    {
        $errorMessages = [];

        $errorMessages['nameErr'] =     FormValidation::validationName($data['name']);
        $errorMessages['jobTitleErr'] = FormValidation::validationJobTitleForContactCCS($data['jobTitle']);
        $errorMessages['companyErr'] =  FormValidation::validationCompany($data['company']);
        $errorMessages['emailErr'] =    FormValidation::validationEmail($data['email']);

        return $this->formatErrorMessages($errorMessages);
    }

    public function validateEsourcingRegister(array $data)
    {
        $errorMessages = [];

        $errorMessages['nameErr'] =     FormValidation::validationName($data['name']);
        $errorMessages['emailErr'] =    FormValidation::validationEmail($data['email']);

        return $this->formatErrorMessages($errorMessages);
    }

    public function validateEsourcingTraining(array $data)
    {
        $errorMessages = [];

        $errorMessages['customerTypeErr'] =     FormValidation::validatioCustomerType($data['customerType']);
        $errorMessages['dateErr'] =             FormValidation::validationDate($data['customerType'], $data['buyerDate'], $data['supplierDate']);
        $errorMessages['nameErr'] =             FormValidation::validationNameForEsourcingTraining($data['name']);
        $errorMessages['phoneErr'] =            FormValidation::validationPhone($data['phone']);
        $errorMessages['emailErr'] =            FormValidation::validationEmail($data['email']);

        return $this->formatErrorMessages($errorMessages);
    }

    public function validateGatedForm(array $data)
    {
        $errorMessages = [];

        $errorMessages['nameErr'] =     FormValidation::validationName($data['name']);
        $errorMessages['jobTitleErr'] = FormValidation::validationJobTitle($data['jobTitle']);
        $errorMessages['companyErr'] =  FormValidation::validationCompany($data['company']);
        $errorMessages['emailErr'] =    FormValidation::validationEmail($data['email']);


        return self::formatErrorMessages($errorMessages);
    }

    public function validateEventsForm(array $data)
    {
        $errorMessages = [];


        $errorMessages['nameErr'] =     FormValidation::validationName($data['name']);
        $errorMessages['jobTitleErr'] = FormValidation::validationJobTitleForContactCCS($data['jobTitle']);
        $errorMessages['emailErr'] =    FormValidation::validationEmail($data['email']);
        $errorMessages['companyErr'] =  FormValidation::validationCompany($data['company']);
        $errorMessages['phoneErr'] =    FormValidation::validationPhone($data['phone']);

        return $this->formatErrorMessages($errorMessages);
    }

    private function formatErrorMessages($errorMessages)
    {

        foreach ($errorMessages as $type => $value) {
            if (!empty($errorMessages[$type]['errors'])) {
                return $errorMessages;
            }
        }

        return false;
    }

    private function checkUTM($utmArray, $params)
    {
        if (empty($utmArray)) {
            return $params;
        }

        foreach ($utmArray as $utmKey => $utmValue) {
            $params = self::setUTM($params, $utmKey, $utmValue);
        }

        return $params;
    }
    private function setUTM($params, $utmKey, $utmValue)
    {
        $utmMap = [
            "utm_campaign" => "Case_Marketing_Campaign__c",
            "utm_content" => "Case_Marketing_Content__c",
            "utm_medium" => "Case_Marketing_Medium__c",
            "utm_source" => "Case_Marketing_Source__c"
        ];

        if (array_key_exists($utmKey, (array)$utmMap)) {
            $params->set($utmMap[$utmKey], $utmValue);
        }

        return $params;
    }

    private function fileAttached()
    {
        return (isset($_FILES['attachment']) && $_FILES['attachment']["size"] != 0);
    }

    private function sendToDocumentUpload()
    {
        if ((!empty($_FILES["attachment"])) && ($_FILES['attachment']['error'] == 0)) {
            try {
                $uploadedFilePath = substr((string) $_FILES["attachment"]["tmp_name"], 0, strripos((string) $_FILES["attachment"]["tmp_name"], '/') + 1);
                $newFilePath = $uploadedFilePath . basename($_FILES["attachment"]["name"]);
                move_uploaded_file($_FILES["attachment"]["tmp_name"], $newFilePath);

                $dataPartFile = DataPart::fromPath($newFilePath);
                $debugStringFromDataPart = $dataPartFile->asDebugString();
                $filename = preg_replace('/[^A-Za-z0-9\_\-\.]/', '_', substr($debugStringFromDataPart, strripos($debugStringFromDataPart, ":") + 2));

                $data = [
                    'typeValidation[]'  => $_FILES["attachment"]["type"],
                    'sizeValidation'    => strval($_FILES["attachment"]["size"]),
                    'documentFile'      => $dataPartFile
                ];

                $formData = new FormDataPart($data);

                $headers = $formData->getPreparedHeaders()->toArray();
                $headers['x-api-key'] = getenv('documentHanding_key');

                $response = $this->client->request('POST', getenv('documentHanding_endpoint'), [
                    'headers'   => $headers,
                    'body'      => $formData->bodyToIterable(),
                ]);

                unlink($newFilePath);
                return $response->toArray()["id"] . "/" . $filename;
            } catch (\Exception $exception) {
                throw new NotFoundHttpException('Failed to upload (Document Upload)', $exception);
                return null;
            }
        }
    }

    public function submitCsatSurvey(Request $request): JsonResponse
    {
        // Decode JSON Payload (Frontend sends application/json)
        $content = $request->getContent();
        $data = json_decode($content, true);

        // Validation
        $rating = $data['rating'] ?? null;
        $comments = $data['feedback-comments'] ?? '';

        // Basic validation: Rating is mandatory, 1-10
        if (!$rating || !is_numeric($rating)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'A valid rating (1-10) is required.'
            ], 400);
        }

        // Prepare Qualtrics Payload
        $qualtricsPayload = [
            'values' => [
                'QID14'      => intval($rating),
                'QID24_TEXT' => $comments,
            ],
            'embeddedData' => [
                'referer'   => $request->headers->get('referer'),
                'userAgent' => $request->headers->get('User-Agent'),
            ]
        ];

        // Get Config from Env
        $apiToken   = getenv('QUALTRICS_API_TOKEN');
        $surveyId   = getenv('QUALTRICS_SURVEY_ID');

        // Check if any of the required env variables are missing or empty
        if (empty($apiToken) || empty($surveyId)) {
            // Log this as a critical error so you see it in your Symfony logs
            $this->logger->critical('Qualtrics API configuration is missing in the .env file.');

            // Return a safe, generic error to the JavaScript frontend
            return new JsonResponse([
                'success' => false,
                'message' => 'The feedback service is currently unavailable. Please make sure the correct configs are setup.'
            ], 500);
        }

        $endpoint = "https://fra1.qualtrics.com/API/v3/surveys/{$surveyId}/responses";

        try {
            // Send Request using existing Symfony HttpClient
            $response = $this->client->request('POST', $endpoint, [
                'headers' => [
                    'X-API-TOKEN'  => $apiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $qualtricsPayload
            ]);

            $statusCode = $response->getStatusCode();

            // Convert response to array (non-blocking)
            $result = $response->toArray(false);

            // Handle Success
            if ($statusCode === 200 && isset($result['result']['responseId'])) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Feedback submitted successfully'
                ], 200);
            }

            // Handle API Rejection (e.g., Bad Request)
            $this->logger->error('Qualtrics API Error: ' . json_encode($result));
            return new JsonResponse([
                'success' => false,
                'message' => 'Survey provider rejected the submission',
            ], 400);
        } catch (\Exception $e) {
            // Handle Network/Server Errors
            $this->logger->error('Qualtrics Connection Failed: ' . $e->getMessage());

            return new JsonResponse([
                'success' => false,
                'message' => 'Internal server error processing feedback',
            ], 500);
        }
    }
}
