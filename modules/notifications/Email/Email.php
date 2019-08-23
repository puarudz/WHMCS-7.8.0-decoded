<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Notification\Email;

use WHMCS\Config\Setting;
use WHMCS\Exception;
use WHMCS\Mail\Template;
use WHMCS\Module\Notification\DescriptionTrait;
use WHMCS\Module\Contracts\NotificationModuleInterface;
use WHMCS\Notification\Contracts\NotificationInterface;
use WHMCS\Utility\Environment\WebHelper;
/**
 * Notification module for delivering notifications via email
 *
 * All notification modules must implement NotificationModuleInterface
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */
class Email implements NotificationModuleInterface
{
    use DescriptionTrait;
    /**
     * Constructor
     *
     * Any instance of a notification module should have the display name and
     * logo filename at the ready.  Therefore it is recommend to ensure these
     * values are set during object instantiation.
     *
     * The Email notification module utilizes the DescriptionTrait which
     * provides methods to fulfill this requirement.
     *
     * @see \WHMCS\Module\Notification\DescriptionTrait::setDisplayName()
     * @see \WHMCS\Module\Notification\DescriptionTrait::setLogoFileName()
     */
    public function __construct()
    {
        $this->setDisplayName('Email')->setLogoFileName('logo.png');
    }
    /**
     * Settings required for module configuration
     *
     * The method should provide a description of common settings required
     * for the notification module to function.
     *
     * For example, if the module connects to a remote messaging service this
     * might be username and password or OAuth token fields required to
     * authenticate to that service.
     *
     * This is used to build a form in the UI.  The values submitted by the
     * admin based on the form will be validated prior to save.
     * @see testConnection()
     *
     * The return value should be an array structured like other WHMCS modules.
     * @link https://developers.whmcs.com/payment-gateways/configuration/
     *
     * For the Email notification module, the module settings are the sender
     * name and email.  Every email notification will use these values.
     * Other email values, like recipients are defined on a per notification
     * basis.
     * @see notifictionSettings()
     *
     * EX.
     * return [
     *     'api_username' => [
     *         'FriendlyName' => 'API Username',
     *         'Type' => 'text',
     *         'Description' => 'Required username to authenticate with message service',
     *     ],
     *     'api_password' => [
     *         'FriendlyName' => 'API Password',
     *         'Type' => 'password',
     *         'Description' => 'Required password to authenticate with message service',
     *     ],
     * ];
     *
     * @return array
     */
    public function settings()
    {
        return ['senderName' => ['FriendlyName' => 'Sender Name', 'Type' => 'text', 'Description' => 'The name the notification should come from.', 'Placeholder' => Setting::getValue('CompanyName')], 'senderEmail' => ['FriendlyName' => 'Sender Email', 'Type' => 'text', 'Description' => 'The email address the notification should come from.', 'Placeholder' => Setting::getValue('Email')]];
    }
    /**
     * Validate settings for notification module
     *
     * This method will be invoked prior to saving any settings via the UI.
     *
     * Leverage this method to verify authentication and/or authorization when
     * the notification service requires a remote connection.
     *
     * For the Email notification module, connectivity details are already
     * defined by the WHMCS core system, and there are no settings which
     * require further validation, so this method will always return TRUE.
     *
     * @param array $settings
     *
     * @return boolean
     */
    public function testConnection($settings)
    {
        return true;
    }
    /**
     * The individual customisable settings for a notification.
     *
     * EX.
     * ['channel' => [
     *     'FriendlyName' => 'Channel',
     *     'Type' => 'dynamic',
     *     'Description' => 'Select the desired channel for notification delivery.',
     *     'Required' => true,
     *     ],
     * ]
     *
     * The "Type" of a setting can be text, textarea, yesno, system and dynamic
     *
     * @see getDynamicField for how to obtain dynamic values
     *
     * For the Email notification module, the notification should be configured
     * to use a email template and one or more recipients.
     *
     * @return array
     */
    public function notificationSettings()
    {
        return ['emailTemplate' => ['FriendlyName' => 'Email Template', 'Type' => 'dynamic', 'Description' => 'Choose the notification email template. You can create new email templates in <a href="' . WebHelper::getAdminBaseUrl() . '/configemailtemplates.php">Email Templates</a>'], 'recipients' => ['FriendlyName' => 'Recipients', 'Type' => 'text', 'Description' => 'Enter recipient email address(es) - separate entries with commas']];
    }
    /**
     * The option values available for a 'dynamic' Type notification setting
     *
     * @see notificationSettings()
     *
     * EX.
     * if ($fieldName == 'channel') {
     *     return [ 'values' => [
     *         ['id' => 1, 'name' => 'Tech Support', 'description' => 'Channel ID',],
     *         ['id' => 2, 'name' => 'Customer Service', 'description' => 'Channel ID',],
     *     ],];
     * } elseif ($fieldName == 'botname') {
     *     $restClient = $this->factoryHttpClient($settings);
     *     $operators = $restClient->get('/operators');
     *     return ['values' => $operators->toArray()];
     * }
     *
     * For the Email notification module, a list of possible email templates is
     * aggregated.
     *
     * @param string $fieldName Notification setting field name
     * @param array $settings Settings for the module
     *
     * @return array
     */
    public function getDynamicField($fieldName, $settings)
    {
        if ($fieldName == 'emailTemplate') {
            $templates = Template::whereType('notification')->get();
            $values = [];
            foreach ($templates as $template) {
                $values[] = ['id' => $template->id, 'name' => $template->name, 'description' => 'Email Template ID'];
            }
            return ['values' => $values];
        }
        return [];
    }
    /**
     * Deliver notification
     *
     * This method is invoked when rule criteria are met.
     *
     * In this method, you should craft an appropriately formatted message and
     * transmit it to the messaging service.
     *
     * For the Email notification module, an email template instance is created
     * along with a collection of merge field data (aggregated from all three
     * method arguments respectively). Those items are provided to the local
     * API 'sendmail' action, where an email message is generated and delivered.
     *
     * @param NotificationInterface $notification A notification to send
     * @param array $moduleSettings Configured settings of the notification module
     * @param array $notificationSettings Configured notification settings set by the triggered rule
     *
     * @throws Exception on error of sending email
     */
    public function sendNotification(NotificationInterface $notification, $moduleSettings, $notificationSettings)
    {
        $to = explode(',', $notificationSettings['recipients']);
        $to = array_filter(array_unique($to));
        if (!$to) {
            throw new Exception('No Recipients Found');
        }
        $mergeFields = ['to' => $to, 'senderName' => $moduleSettings['senderName'], 'senderEmail' => $moduleSettings['senderEmail'], 'notification_title' => $notification->getTitle(), 'notification_url' => $notification->getUrl(), 'notification_message' => $notification->getMessage(), 'notification_attributes' => []];
        foreach ($notification->getAttributes() as $attribute) {
            $mergeFields['notification_attributes'][] = ['label' => $attribute->getLabel(), 'value' => $attribute->getValue(), 'url' => $attribute->getUrl(), 'style' => $attribute->getStyle(), 'icon' => $attribute->getIcon()];
        }
        /** @var Template $email */
        try {
            $email = Template::findOrFail($notificationSettings['emailTemplate']);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
        $response = localAPI('sendemail', ['messagename' => $email->name, 'id' => 1, 'customvars' => $mergeFields]);
        if (array_key_exists('error', $response)) {
            throw new Exception($response['error']);
        }
    }
}

?>