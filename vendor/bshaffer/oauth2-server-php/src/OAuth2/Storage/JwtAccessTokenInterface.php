<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace OAuth2\Storage;

/**
 * No specific methods, but allows the library to check "instanceof"
 * against interface rather than class
 *
 * @author Brent Shaffer <bshafs at gmail dot com>
 */
interface JwtAccessTokenInterface extends AccessTokenInterface
{
}

?>