<?php

namespace craftcom\controllers\id;

use Craft;
use craft\elements\Category;
use craft\elements\User;
use craftcom\Module;
use yii\helpers\Json;
use yii\web\Response;
use craft\commerce\Plugin as Commerce;
use craftcom\plugins\Plugin;
use craft\commerce\elements\Order;

/**
 * Class CraftIdController
 *
 * @package craftcom\controllers\id
 */
class CraftIdController extends BaseController
{
    // Public Methods
    // =========================================================================

    /**
     * Handles /v1/craft-id requests.
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        // Current user
        $currentUserId = Craft::$app->getRequest()->getParam('userId');
        $currentUser = Craft::$app->getUsers()->getUserById($currentUserId);

        // Craft ID config
        $craftIdConfig = Craft::$app->getConfig()->getConfigFromFile('craftid');
        $enableCommercialFeatures = $craftIdConfig['enableCommercialFeatures'];

        // Data
        $data = [
            'currentUser' => [
                'id' => $currentUser->id,
                'email' => $currentUser->email,
                'username' => $currentUser->username,
                'firstName' => $currentUser->firstName,
                'lastName' => $currentUser->lastName,
                'developerName' => $currentUser->developerName,
                'developerUrl' => $currentUser->developerUrl,
                'location' => $currentUser->location,
                'enablePluginDeveloperFeatures' => ($currentUser->isInGroup('developers') ? true : false),
                'enableShowcaseFeatures' => ($currentUser->enableShowcaseFeatures == 1 ? true : false),
                'businessName' => $currentUser->businessName,
                'businessVatId' => $currentUser->businessVatId,
                'businessAddressLine1' => $currentUser->businessAddressLine1,
                'businessAddressLine2' => $currentUser->businessAddressLine2,
                'businessCity' => $currentUser->businessCity,
                'businessState' => $currentUser->businessState,
                'businessZipCode' => $currentUser->businessZipCode,
                'businessCountry' => $currentUser->businessCountry,
                'groups' => $currentUser->getGroups(),
                'photoId' => ($currentUser->getPhoto() ? $currentUser->getPhoto()->getId() : null),
                // 'photoUrl' => ($currentUser->getPhoto() ? $currentUser->getPhoto()->getUrl() : null),
                'photoUrl' => $currentUser->getThumbUrl(200),
            ],
            'apps' => Module::getInstance()->getOauth()->getApps(),
            'plugins' => $this->_plugins($currentUser),
            'cmsLicenses' => $this->_cmsLicenses($currentUser),
            'pluginLicenses' => $this->_pluginLicenses($currentUser),
            'customers' => $this->_customers($currentUser),
            'sales' => $this->_sales(),
            'upcomingInvoice' => $this->_upcomingInvoice(),
            'invoices' => $this->_invoices(),
            'categories' => $this->_pluginCategories(),
            'enableCommercialFeatures' => $enableCommercialFeatures
        ];

        return $this->asJson($data);
    }

    // Private Methods
    // =========================================================================

    /**
     * @param User $user
     *
     * @return array
     */
    private function _plugins(User $user): array
    {
        $ret = [];

        foreach ($user->getPlugins() as $plugin) {
            $ret[] = $this->pluginTransformer($plugin);
        }

        return $ret;
    }

    /**
     * @param User $user
     *
     * @return array CMS licenses.
     */
    private function _cmsLicenses(User $user): array
    {
        $results = Module::getInstance()->getCmsLicenseManager()->getLicensesByOwner($user->id);

        $licenses = [];

        foreach($results as $result) {
            $license = $result->toArray();
            $pluginLicensesResults = Module::getInstance()->getPluginLicenseManager()->getLicensesByCmsLicenseId($result->id);

            $pluginLicenses = [];

            foreach ($pluginLicensesResults as $key => $pluginLicensesResult) {
                $pluginLicense = $pluginLicensesResult->toArray();

                $plugin = null;

                if($pluginLicensesResult->pluginId) {
                    $plugin = Plugin::find()->id($pluginLicensesResult->pluginId)->status(null)->one();
                }

                $pluginLicense['plugin'] = $plugin;

                $pluginLicenses[] = $pluginLicense;
            }



            $license['pluginLicenses'] = $pluginLicenses;
            $licenses[] = $license;
        }

        return $licenses;
    }

    /**
     * @param User $user
     *
     * @return array Plugin licenses.
     */
    private function _pluginLicenses(User $user): array
    {
        $results = Module::getInstance()->getPluginLicenseManager()->getLicensesByOwner($user->id);

        $licenses = [];

        foreach($results as $result) {
            $license = $result->toArray();


            // Plugin

            $plugin = null;

            if($result->pluginId) {
                $plugin = Plugin::find()->id($result->pluginId)->status(null)->one();
            }

            $license['plugin'] = $plugin;


            // CMS License

            $cmsLicense = null;

            if($result->cmsLicenseId) {
                $cmsLicense = Module::getInstance()->getCmsLicenseManager()->getLicenseById($result->cmsLicenseId);
            }

            $license['cmsLicense'] = $cmsLicense;

            $licenses[] = $license;
        }

        return $licenses;
    }

    /**
     * @param User $user
     *
     * @return array
     */
    private function _customers(User $user): array
    {
        return [
            [
                'id' => 1,
                'email' => 'ben@pixelandtonic.com',
                'username' => 'benjamin',
                'fullName' => 'Benjamin David',
            ],
            [
                'id' => 2,
                'email' => 'brandon@pixelandtonic.com',
                'username' => 'brandon',
                'fullName' => 'Brandon Kelly',
            ]
        ];
    }

    /**
     * @return array
     */
    private function _sales(): array
    {
        return [
            [
                'id' => 3,
                'plugin' => ['id' => 6, 'name' => 'Analytics'],
                'type' => 'license',
                'grossAmount' => 99.00,
                'netAmount' => 79.20,
                'customer' => [
                    'id' => 2,
                    'name' => 'Brandon Kelly',
                    'email' => 'brandon@pixelandtonic.com',
                ],
                'date' => date('Y-m-d'),
            ],
            [
                'id' => 2,
                'plugin' => ['id' => 6, 'name' => 'Analytics'],
                'type' => 'renewal',
                'grossAmount' => 29.00,
                'netAmount' => 23.20,
                'customer' => [
                    'id' => 1,
                    'name' => 'Benjamin David',
                    'email' => 'ben@pixelandtonic.com',
                ],
                'date' => date('Y-m-d'),
            ],
            [
                'id' => 1,
                'plugin' => ['id' => 6, 'name' => 'Analytics'],
                'type' => 'license',
                'grossAmount' => 99.00,
                'netAmount' => 79.20,
                'customer' => [
                    'id' => 1,
                    'name' => 'Benjamin David',
                    'email' => 'ben@pixelandtonic.com',
                ],
                'date' => date('Y-m-d', strtotime('-1 year')),
            ],
        ];
    }

    /**
     * @return array
     */
    private function _upcomingInvoice(): array
    {
        return [
            'datePaid' => date('Y-m-d'),
            'paymentMethod' => [
                'type' => 'visa',
                'last4' => '2424',
            ],
            'items' => [
                ['id' => 6, 'name' => 'Analytics', 'amount' => 29, 'type' => 'renewal'],
                ['id' => 8, 'name' => 'Social', 'amount' => 99, 'type' => 'license']
            ],
            'totalPrice' => 128,
            'customer' => [
                'id' => 1,
                'name' => 'Benjamin David',
                'email' => 'ben@pixelandtonic.com',
            ],
        ];
    }

    /**
     * @return array
     */
    private function _invoices(): array
    {
        $customer = Commerce::getInstance()->getCustomers()->getCustomer();

        $query = Order::find();
        $query->customer($customer);
        $query->isCompleted(true);
        $query->limit(null);
        $query->orderBy('dateOrdered desc');

        $results = $query->all();

        $orders = [];

        foreach ($results as $result) {
            $order = $result->toArray();
            $order['shortNumber'] = $result->getShortNumber();
            $order['itemTotal'] = $result->getItemTotal();
            $order['totalPrice'] = $result->getTotalPrice();
            $order['billingAddress'] = $result->getBillingAddress();


            // Payment source

            $paymentSource = $result->getPaymentSource();

            if($paymentSource) {
                $order['paymentSource'] = $paymentSource->toArray();

                $response = Json::decode($paymentSource->response);

                if (isset($response['object']) && $response['object'] === 'card') {
                    $order['card'] = $response;
                }
            }


            // Line Items

            $lineItems = [];

            foreach($result->lineItems as $lineItem) {
                $row = $lineItem->toArray();
                $row['description'] = $lineItem->getDescription();
                $row['subtotal'] = $lineItem->getSubtotal();
                $lineItems[] = $row;
            }

            $order['lineItems'] = $lineItems;


            // Transactions
            $order['transactions'] = $result->getTransactions();

            $orders[] = $order;
        }

        return $orders;
    }

    /**
     * @return array
     */
    private function _pluginCategories(): array
    {
        $ret = [];
        $categories = Category::find()
            ->group('pluginCategories')
            ->all();

        foreach ($categories as $category) {
            $ret[] = [
                'id' => $category->id,
                'title' => $category->title,
            ];
        }

        return $ret;
    }
}
