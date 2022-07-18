<?php

declare(strict_types=1);

namespace App\Controller;

use App\Validation\FormValidation;
use App\Validation\ContactCCSFormValidation;
use App\Validation\EsourcingRegisterFormValidation;
use App\Validation\EsourcingTrainingFormValidation;
use App\Validation\GatedFormValidation;
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

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->api = new RestData(
            getenv('APP_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
        );
        $this->api->setContentType('esourcing_dates');
        $psr6Cache = new Psr16Cache($cache);
        $this->api->setCache($psr6Cache);

        $this->client = HttpClient::create();
    }

    public function esourcingRegisterSubmit(Request $request)
    {
        $params = $request->request;

        ControllerHelper::honeyPot($params->get('surname', null));

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
            $params->set('recordType', '012b00000005NWC');
            $params->set('priority', 'Green');
            $params->set('origin', 'Website - eSourcing Access');
            $params->set('orgid', ControllerHelper::getOrgId());

            $response = $this->client->request('POST', getenv('SALESFORCE_WEB_TO_CASE_URL'), [
                'query'             => $params->all(),
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
            $params->set('priority', 'Green');
            $params->set('origin', 'Website - eSourcing Training');
            $params->set('orgid', ControllerHelper::getOrgId());

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
        $referrer = $request->headers->get('referer');

        $data = [
            'referrer' => $referrer
        ];

        return $this->render('forms/22-contact.html.twig', $data);
    }

    public function contactCcsSubmit(Request $request)
    {
        $params = $request->request;

        ControllerHelper::honeyPot($params->get('surname', null));

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

        if ($params->get('complaint')) {
            $formData['complaint'] = $params->get('complaint', null);
        }

        if (!empty($formData)) {
            $formErrors = $this->validateContactCCS($formData);

            if ($formErrors) {
                return $this->render('forms/22-contact.html.twig', [
                    'referrer' => $params->get('00N4L000009OPAj', null),
                    'formErrors' => $formErrors,
                    'formData' => $formData,
                ]);
            } else {
                $params->set('subject', 'Contact CCS');
                $params->set('00Nb0000009IXEW', 'General-Enquiry');
                $params->set('recordType', '012b00000005NWC');
                $params->set('priority', 'Green');
                $params->set('orgid', ControllerHelper::getOrgId());

                $response = $this->client->request('POST', getenv('SALESFORCE_WEB_TO_CASE_URL'), [
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

    public function newsletters(Request $request)
    {
        $params = $request->request;

        ControllerHelper::honeyPot($params->get('surname', null));

        $formData = [
            'name'          => $params->get('name', null),
            'email'         => $params->get('email', null),
            'company'       => $params->get('company', null),
            'jobTitle'      => $params->get('00Nb0000009IXEs', null),
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

                $response = $this->client->request('POST', getenv('SALESFORCE_WEB_TO_CASE_URL'), [
                    'query' => $params->all(),
                ]);

                if (!is_null($params->get('debug'))) {
                    return new Response(
                        $response->getContent()
                    );
                } else {
                    return $this->redirectToRoute('form_newsletter_thanks');
                }
            }
        }
    }

    public static function sendToSalesforce($params, $data, $campaignCode, $description)
    {
        $formErrors = self::validateGatedForm($data);

        if (!$formErrors) {
            $client = HttpClient::create();

            $params->set('subject', $campaignCode);
            $params->set('00Nb0000009IXEW', $campaignCode);
            $params->set('00Nb0000009IXEn', '1');
            $params->set('recordType', '012b00000005NWC');
            $params->set('priority', 'Green');
            $params->set('description', $description);
            $params->set('orgid', ControllerHelper::getOrgId());

            $client->request('POST', getenv('SALESFORCE_WEB_TO_CASE_URL'), [
                  'query' => $params->all(),
              ]);
        }

        return $formErrors;
    }

    public function validateContactCCS(array $data)
    {
        $errorMessages = [];

        $errorMessages['nameErr'] = ContactCCSFormValidation::validationName($data['name']);
        $errorMessages['jobTitleErr'] = ContactCCSFormValidation::validationJobTitle($data['jobTitle']);
        $errorMessages['emailErr'] = ContactCCSFormValidation::validationEmail($data['email']);
        $errorMessages['phoneErr'] = ContactCCSFormValidation::validationPhone($data['phone'], $data['callback']);
        $errorMessages['companyErr'] = ContactCCSFormValidation::validationCompany($data['company']);
        $errorMessages['moreDetailErr'] = ContactCCSFormValidation::validationMoreDetial($data['moreDetail']);

        return $this->formatErrorMessages($errorMessages);
    }

    private function validateNewsletterForm($data)
    {
        $errorMessages = [];

        $errorMessages['nameErr'] = FormValidation::validationName($data['name']);
        $errorMessages['jobTitleErr'] = FormValidation::validationJobTitle($data['jobTitle']);
        $errorMessages['emailErr'] = FormValidation::validationEmail($data['email']);
        $errorMessages['companyErr'] = FormValidation::validationCompany($data['company']);


        foreach ($errorMessages as $type => $value) {
            if (!empty($errorMessages[$type]['errors'])) {
                return $errorMessages;
            }
        }

        return false;
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

    public static function validateGatedForm(array $data)
    {
        $errorMessages = [];

        $errorMessages['nameErr'] = GatedFormValidation::validationName($data['name']);
        $errorMessages['jobTitleErr'] = GatedFormValidation::validationJobTitle($data['jobTitle']);
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
