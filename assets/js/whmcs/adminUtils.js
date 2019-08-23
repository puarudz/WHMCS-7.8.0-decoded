/**
 * General utilities module
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */
(function(module) {
    if (!WHMCS.hasModule('adminUtils')) {
        WHMCS.loadModule('adminUtils', module);
    }
})(
function () {
    this.getAdminRouteUrl = function (path) {
        return whmcsBaseUrl + "/index.php?rp=" + adminBaseRoutePath + path;
    };

    this.normaliseStringValue = function(status) {
        return status ? status.toLowerCase().replace(/\s/g, '-') : '';
    }

    return this;
});
