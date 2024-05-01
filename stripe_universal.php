<?php
/**
 * Stripe Universal processing gateway. Redirect user to Stripe pre-build page
 *
 * The Stripe API can be found at: https://stripe.com/docs/api
 */
class StripeUniversal extends NonmerchantGateway
{
    /**
     * @var array An array of meta data for this gateway
     */
    private $meta;

    private $base_url = 'https://api.stripe.com/v1/';

    /**
     * Construct a new merchant gateway
     */
    public function __construct()
    {
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this module
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this module
        Language::loadLang('stripe_universal', null, dirname(__FILE__) . DS . 'language' . DS);

        // Load product configuration required by this module
        Configure::load('stripe_universal', dirname(__FILE__) . DS . 'config' . DS);
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettings(array $meta = null)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('settings', 'default');
        $this->view->setDefaultView('components' . DS . 'gateways' . DS . 'nonmerchant' . DS . 'stripe_universal' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        $this->view->set('meta', $meta);

        return $this->view->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function editSettings(array $meta)
    {
        // Validate the given meta data to ensure it meets the requirements
        $rules = [
            'secret_key' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('StripeUniversal.!error.secret_key.empty', true)
                ],
                'valid' => [
                    'rule' => [[$this, 'validateConnection']],
                    'message' => Language::_('StripeUniversal.!error.secret_key.valid', true)
                ]
            ]
        ];

        // @TODO enable a subset of available payment method
        // https://stripe.com/docs/api/checkout/sessions/create#create_checkout_session-customer

        $this->Input->setRules($rules);

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function encryptableFields()
    {
        return ['secret_key'];
    }

    /**
     * {@inheritdoc}
     */
    public function setMeta(array $meta = null)
    {
        $this->meta = $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function requiresCustomerPresent()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function buildProcess($contact_info, $amount, $invoice_amounts = null, $options = null)
    {
        $this->loadApi();

        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = $this->makeView(
            'payment_button',
            'default',
            str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS)
        );

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Find Client
        Loader::loadModels($this, ['Contacts']);
        $contact = $this->Contacts->get($contact_info['id']);

        // Create Payment Session for user to jump to
        // FIXME: Find a way to reuse existing session instead of creating one every time
        try {
            $sessionObj = [
                'customer_email' => $contact->email,
                'line_items' => $this->getLineItems($amount, $invoice_amounts),
                'mode' => 'payment',
                'success_url' => $options['return_url'] . "&session_id={CHECKOUT_SESSION_ID}",
                'cancel_url' => $options['return_url'] . "&canceled=true",
                'metadata' => array(
                    'client_id' => $contact_info['client_id'],
                    'invoices' => base64_encode(serialize($invoice_amounts)),
                )
            ];
            $session = \Stripe\Checkout\Session::create($sessionObj);
        } catch (Exception $e) {
            $this->Input->setErrors(['api' => ['internal' => $e->getMessage()]]);
            return;
        }


        $this->view->set('goto_url', $session->url);

        return $this->view->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function success(array $get, array $post)
    {
        $this->loadApi();

        if (array_key_exists('session_id', $get)) {
            $session_id = $get['session_id'];
            $session = \Stripe\Checkout\Session::retrieve($session_id, [
                'expand' => ['payment_intent']
            ]);
            return $this->handleCheckoutSession($session);
        }

        if ($get['canceled'] === "true") {
            $this->Input->setErrors([
                "exceptions" => [
                    'message' => Language::_('StripeUniversal.!error.payment_canceled', true),
                ]
            ]);
        } else {
            $this->Input->setErrors([
                "session_id" => [
                    'message' => Language::_('StripeUniversal.!error.session_id.missing', true),
                ]
            ]);
        }


        return [];
    }

    private function handleCheckoutSession(\Stripe\Checkout\Session $session) {
        $status = "pending";
        if ($session->payment_status === "paid") {
            $status = "approved";
        } else {
            switch ($session->status) {
                case "expired":
                    $this->Input->setErrors([
                        'payment_status' => [
                            'message' => Language::_('StripeUniversal.!error.payment_expired', true),
                        ]
                    ]);
                    $status = "void";
                    break;
                case "complete":
                    $this->Input->setErrors([
                        'payment_status' => [
                            'message' => Language::_('StripeUniversal.!error.payment_in_progress', true),
                        ]
                    ]);
                    break;
                case "open":
                default:
                    $this->Input->setErrors([
                        'payment_status' => [
                            'message' => Language::_('StripeUniversal.!error.payment_not_received', true),
                        ]
                    ]);
            }
        }

        $metadata = $this->extractMetadata($session->metadata);
        if ($metadata === false) {
            return [];
        }

        $currency = strtoupper($session->currency);
        $amount = $this->formatAmount(
            $session->amount_total,
            $currency,
            'from'
        );

        if (isset($session->currency_conversion)) {
            $currency = strtoupper($session->currency_conversion->source_currency);
            $amount = $this->formatAmount(
                $session->currency_conversion->amount_total,
                $currency,
                'from'
            );
        }

        return [
            'client_id' => $metadata['client_id'],
            'amount' => $amount,
            'currency' => $currency,
            'status' => $status,
            'reference_id' => $session->id,
            'transaction_id' => $session->payment_intent,
            'invoices' => $metadata['invoices'],
        ];
    }

    private function extractMetadata(\Stripe\StripeObject $metadata)
    {
        if ($metadata === null) {
            $this->Input->setErrors([
                'metadata' => [
                    'message' => Language::_('StripeUniversal.!error.metadata.missing', true),
                ]
            ]);

            return false;
        }

        $metadata = $metadata->toArray();
        $client_id = $metadata['client_id'];

        if (!$metadata['client_id']) {
            $this->Input->setErrors([
                'metadata' => [
                    'message' => Language::_('StripeUniversal.!error.metadata.missing_client_id', true),
                ]
            ]);

            return false;
        }

        return [
            'client_id' => $client_id,
            'invoices' => unserialize(base64_decode($metadata['invoices'])),
        ];
    }

    /**
     * Loads the API if not already loaded, can only be called after constructor has done its work
     */
    private function loadApi()
    {
        if (!class_exists('\Stripe\Stripe', false)) {
            Loader::load(dirname(__FILE__) . DS . 'vendor' . DS . 'stripe' . DS . 'stripe-php' . DS . 'init.php');
        }
        Stripe\Stripe::setApiKey((isset($this->meta['secret_key']) ? $this->meta['secret_key'] : null));

        // Include identifying information about this being a gateway for Blesta
        Stripe\Stripe::setAppInfo('Blesta ' . $this->getName(), $this->getVersion(), 'https://blesta.com');
    }


    /**
     * Convert amount from decimal value to integer representation of cents
     *
     * @param float $amount
     * @param string $currency
     * @param string $direction
     * @return int The amount in cents
     */
    private function formatAmount($amount, $currency, $direction = 'to')
    {
        $non_decimal_currencies = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY',
            'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'VUV', 'XAF', 'XOF', 'XPF'];

        if (is_numeric($amount) && !in_array($currency, $non_decimal_currencies)) {
            if ($direction === 'to') {
                $amount *= 100;
            } else {
                $amount /= 100;
                return round($amount, 2);
            }
        }
        
        return (int)round($amount);
    }


    /**
     * Checks whether a key can be used to connect to the Stripe API
     *
     * @param string $secret_key The API to connect with
     * @return boolean True if a successful API call was made, false otherwise
     */
    public function validateConnection($secret_key)
    {
        $this->loadApi();

        $success = true;
        // Skip test if test key is given
        if (substr($secret_key, 0, 7) == 'sk_test') {
           return $success;
        }

        try {
            // Attempt to make an API request
            Stripe\Stripe::setApiKey($secret_key);
            Stripe\Balance::retrieve();
        } catch (Exception $e) {
            $success = false;
        }

        return $success;
    }

    /**
     * Retrieves the description for CC charges
     *
     * @param float total amount
     * @param array|null $invoice_amounts An array of invoice amounts (optional)
     * @return array[] Line Items
     */
    private function getLineItems(float $amount, array $invoice_amounts = null)
    {
        $defaultItem = [[
            'price_data' => [
                'currency' =>  $this->currency,
                'product_data' => [
                    'name' =>  Language::_('StripeUniversal.charge_description_default', true),
                ],
                'unit_amount_decimal' => $this->formatAmount($amount, $this->currency, 'to'),
            ],
            'quantity' => 1,
        ]];

        // No invoice amounts, set a default description
        if (empty($invoice_amounts)) {
            return $defaultItem;
        }

        Loader::loadModels($this, ['Invoices']);

        // Create a list of invoices being paid
        $id_codes = [];
        foreach ($invoice_amounts as $invoice_amount) {
            if (($invoice = $this->Invoices->get($invoice_amount['id']))) {
                $id_codes[] = array(
                    "id" => $invoice->id_code,
                    "amount" => $invoice_amount['amount'],
                );
            }
        }

        // Use the default description if there are no valid invoices
        if (empty($id_codes)) {
            return $defaultItem;
        }

        $result = [];
        foreach ($id_codes as $id_code) {
            $result[] = [
                'price_data' => [
                    'currency' =>  $this->currency,
                    'product_data' => [
                        'name' =>  Language::_('StripeUniversal.charge_description', true, $id_code['id']),
                    ],
                    'unit_amount' => $this->formatAmount($id_code['amount'], $this->currency, 'to'),
                ],
                'quantity' => 1,
            ];
        }

        return $result;
    }


    /**
     * Validates the incoming POST/GET response from the gateway to ensure it is
     * legitimate and can be trusted.
     *
     * @param array $get The GET data for this request
     * @param array $post The POST data for this request
     * @return array An array of transaction data, sets any errors using Input if the data fails to validate
     */
    public function validate(array $get, array $post)
    {
        $this->loadApi();

        // Get event payload
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;

        try {
            $webhook_secret = $this->meta['webhook_secret'] ?? null;
            if ($webhook_secret) {
                $event = Stripe\Webhook::constructEvent(
                    $payload,
                    $sig_header,
                    $webhook_secret
                );
            } else {
                $event = \Stripe\Event::constructFrom($payload);
            }
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            $this->log($this->base_url . 'Webhook - invalid_signature', $e->getMessage());
            $this->Input->setErrors(['event' => ['internal' => "invalid_signature"]]);
            
            return [];
        } catch (\UnexpectedValueException $e) {
            $this->log($this->base_url . 'Webhook - invalid_payload',  $e->getMessage());
            $this->Input->setErrors(['event' => ['internal' => "invalid_payload"]]);

            return [];
        } catch (Exception $e) {
            $this->log($this->base_url . "Webhook - unknown", $e->getMessage());
            $this->Input->setErrors(['event' => ['internal' => "unknown"]]);

            return [];
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                return $this->handleCheckoutSession($event->data->object);
                break;
        };

        return [];
    }
}
