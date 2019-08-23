<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Authentication;

class PasswordReset
{
    public function sendPasswordResetEmail($email)
    {
        $client = \WHMCS\User\Client::where("email", $email)->where("status", "!=", "Closed")->first();
        $contact = null;
        if (!$client) {
            $contact = \WHMCS\User\Client\Contact::where("email", $email)->where("subaccount", 1)->whereHas("client", function (\Illuminate\Database\Eloquent\Builder $query) {
                $query->where("status", "!=", "Closed");
            })->first();
            if ($contact) {
                $client = $contact->client;
            }
        }
        if (!$client && !$contact) {
            return NULL;
        }
        $entity = $contact ?: $client;
        $resetKey = hash("sha256", $entity->id . mt_rand(100000, 999999) . $entity->passwordHash);
        $entity->passwordResetKey = $resetKey;
        $entity->passwordResetKeyExpiryDate = \WHMCS\Carbon::now()->addHours(2);
        $entity->save();
        $resetUrl = fqdnRoutePath("password-reset-use-key", $resetKey);
        sendMessage("Password Reset Validation", $client->id, array("pw_reset_url" => $resetUrl, "contactid" => $contact ? $contact->id : 0));
        logActivity("Password Reset Requested", $client->id);
    }
    public function changeUserPassword($user, $newPassword)
    {
        if (!$user instanceof \WHMCS\User\Client && !$user instanceof \WHMCS\User\Client\Contact) {
            throw new \WHMCS\Exception\Validation\InvalidValue("Invalid user argument");
        }
        $hasher = new \WHMCS\Security\Hash\Password();
        $user->passwordHash = $hasher->hash(\WHMCS\Input\Sanitize::decode($newPassword));
        $user->passwordResetKey = null;
        $user->passwordResetKeyExpiryDate = null;
        $user->save();
        $userId = 0;
        $contactId = 0;
        if ($user instanceof \WHMCS\User\Client\Contact) {
            run_hook("ContactChangePassword", array("userid" => $user->client->id, "contactid" => $user->id, "password" => $newPassword));
            $userId = $user->client->id;
            $contactId = $user->id;
        } else {
            if ($user instanceof \WHMCS\User\Client) {
                run_hook("ClientChangePassword", array("userid" => $user->id, "password" => $newPassword));
                $userId = $user->id;
                $contactId = 0;
            }
        }
        logActivity("Password Reset Completed", $userId);
        sendMessage("Password Reset Confirmation", $userId, array("contactid" => $contactId));
    }
}

?>