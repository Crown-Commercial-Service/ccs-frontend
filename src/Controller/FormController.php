<?php

declare(strict_types=1);

namespace App\Controller;

use App\Validation\ContactCCSFormValidation;
use App\Validation\EsourcingRegisterFormValidation;
use App\Validation\EsourcingTrainingFormValidation;
use App\Validation\GatedFormValidation;
use App\Controller\WhitepaperController;
use Studio24\Frontend\Cms\RestData;
use Studio24\Frontend\ContentModel\ContentModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Psr\SimpleCache\CacheInterface;

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

    public function __construct(CacheInterface $cache)
    {
        $this->api = new RestData(
            getenv('APP_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
        );
        $this->api->setContentType('esourcing_dates');
        $this->api->setCache($cache);

        $this->client = HttpClient::create();
    }

    public function esourcingRegisterSubmit(Request $request)
    {
        $params = $request->request;

        $formData = [
            'name'      => $params->get('name', null),
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
            $params->set('origin', 'Website - eSourcing Access');

            $response = $this->client->request('POST', getenv('SALESFORCE_WEB_TO_CASE_URL'), [
                'query' => $params->all(),
            ]);

            if (!is_null($params->get('debug'))) {
                return new Response($response->getContent());
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
     * @throws \Studio24\Frontend\Exception\ContentFieldException
     * @throws \Studio24\Frontend\Exception\ContentFieldNotSetException
     * @throws \Studio24\Frontend\Exception\ContentTypeNotSetException
     * @throws \Studio24\Frontend\Exception\FailedRequestException
     * @throws \Studio24\Frontend\Exception\PermissionException
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

        $formData = [
            "customerType"  => $params-> get('customer-type', null),
            "buyerDate"     => $params-> get('buyer-training-dates', null),
            "supplierDate"  => $params-> get('supplier-training-dates', null),
            'name'          => $params->get('name', null),
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
            $params->set('origin', 'Website - eSourcing Training');

            $response = $this->client->request('POST', getenv('SALESFORCE_WEB_TO_CASE_URL'), [
                'query'         => $params->all(),
            ]);

            if (!is_null($params->get('debug'))) {
                return new Response($response->getContent());
            }
        }

        return $this->redirectToRoute('form_esourcing_training_thanks');
    }

    public function contactCCS(Request $request)
    {

        $params = $request->request;

        if (!empty($_REQUEST['surname']) && (bool) $_REQUEST['surname'] == true) {
            die;
        }

        // form data used for validation and to remember values when user submits form
        $formData = [
            'enquiryType'   => $params->get('origin', null),
            'name'          => $params->get('name', null),
            'email'         => $params->get('email', null),
            'phone'         => $params->get('phone', null),
            'company'       => $params->get('company', null),
            'jobTitle'      => $params->get('00Nb0000009IXEs', null),
            'postCode'      => $params->get('post-code', null),
            'moreDetail'    =>  $params->get('more-detail', null),
            'callback'      => $params->get('00Nb0000009IXEg', null)
        ];

        // check if complaint type exists and add to form data
        if ($params->get('complaint')) {
            $formData['complaint'] = $params->get('complaint', null);
        }
        // check for submitted data
        if (!empty($formData)) {
            $formErrors = $this->validateContactCCS($formData);

            // if there are errors re-render contact form with errors and form values
            if ($formErrors) {
                return $this->render('forms/22-contact.html.twig', [
                    'formErrors' => $formErrors,
                    'formData' => $formData,
                ]);
            } else {
                // explicitly set campaign codes for contact form
                $params->set('subject', 'Contact CCS');
                $params->set('00Nb0000009IXEW', 'General-Enquiry');

                $org_id = getenv('APP_ENV') === 'prod' ? getenv('ORG_ID_PROD') : getenv('ORG_ID_TEST');
                $params->set('orgid', $org_id);
                // send to salesforce
                $response = $this->client->request('POST', getenv('SALESFORCE_WEB_TO_CASE_URL'), [
                    // these values are automatically encoded before including them in the URL
                    'query' => $params->all(),
                ]);
                if (!is_null($params->get('debug'))) {
                    return new Response(
                        $response->getContent()
                    );
                } elseif ($params->get('origin') == 'Website - Enquiry') {
                    return $this->redirectToRoute('form_contact_thanks');
                } else {
                    return $this->redirectToRoute('form_contact_thanks_complaint');
                }
            }
        }
    }

    public function gatedFormErrors($formData)
    {
        // validate form data
        $formErrors = self::validateGatedForm($formData);

        return $formErrors;
    }

    public function sendToSalesforce($params, $data, $campaignCode)
    {
        $formErrors = self::gatedFormErrors($data);

        if (!$formErrors) {
              // create client
              $client = HttpClient::create();

              $params->set('subject', $campaignCode);
              $params->set('00Nb0000009IXEW', $campaignCode);

              $response = $client->request('POST', getenv('SALESFORCE_WEB_TO_CASE_URL'), [
                  // these values are automatically encoded before including them in the URL
                  'query' => $params->all(),
              ]);
        }

        return $formErrors;
    }

    public function validateContactCCS(array $data)
    {
        $errorMessages = [];

        $errorMessages['nameErr'] = ContactCCSFormValidation::validationName($data['name']);
        $errorMessages['emailErr'] = ContactCCSFormValidation::validationEmail($data['email']);
        $errorMessages['phoneErr'] = ContactCCSFormValidation::validationPhone($data['phone'], $data['callback']);
        $errorMessages['companyErr'] = ContactCCSFormValidation::validationCompany($data['company']);
        $errorMessages['jobTitleErr'] = ContactCCSFormValidation::validationJobTitle($data['jobTitle']);
        $errorMessages['moreDetailErr'] = ContactCCSFormValidation::validationMoreDetial($data['moreDetail']);

        return $this->formatErrorMessages($errorMessages);
    }

    public function validateEsourcingRegister(array $data)
    {
        $errorMessages = [];

        $errorMessages['nameErr'] = EsourcingRegisterFormValidation::validationName($data['name']);
        $errorMessages['emailErr'] = EsourcingRegisterFormValidation::validationEmail($data['email']);
        $errorMessages['phoneErr'] = EsourcingRegisterFormValidation::validationPhone($data['phone']);

        return $this->formatErrorMessages($errorMessages);
    }

    public function validateEsourcingTraining(array $data)
    {
        $errorMessages = [];

        $errorMessages['customerTypeErr'] = EsourcingTrainingFormValidation::validatioCustomerType($data['customerType']);
        $errorMessages['dateErr'] = EsourcingTrainingFormValidation::validationDate($data['customerType'], $data['buyerDate'], $data['supplierDate']);

        $errorMessages['nameErr'] = EsourcingTrainingFormValidation::validationName($data['name']);
        $errorMessages['emailErr'] = EsourcingTrainingFormValidation::validationEmail($data['email']);
        $errorMessages['phoneErr'] = EsourcingTrainingFormValidation::validationPhone($data['phone']);

        return $this->formatErrorMessages($errorMessages);
    }

    public function validateGatedForm(array $data)
    {
        $errorMessages = [];

        $errorMessages['nameErr'] = GatedFormValidation::validationName($data['name']);
        $errorMessages['emailErr'] = GatedFormValidation::validationEmail($data['email']);
        $errorMessages['phoneErr'] = GatedFormValidation::validationPhone($data['phone']);
        $errorMessages['companyErr'] = GatedFormValidation::validationCompany($data['company']);

        return self::formatErrorMessages($errorMessages);
    }

    public function formatErrorMessages($errorMessages)
    {

        foreach ($errorMessages as $type => $value) {
            if (!empty($errorMessages[$type]['errors'])) {
                return $errorMessages;
            }
        }

        return false;
    }
}
