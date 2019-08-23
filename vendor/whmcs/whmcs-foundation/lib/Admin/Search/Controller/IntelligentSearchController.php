<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Search\Controller;

class IntelligentSearchController extends AbstractSearchController
{
    public function getSearchTerm(\WHMCS\Http\Message\ServerRequest $request)
    {
        return array("term" => $request->get("searchterm", ""), "hideInactive" => $request->get("hide_inactive", 1), "numResults" => $request->get("numresults", "10"), "more" => $request->get("more", ""));
    }
    public function getSearchable()
    {
        return new \WHMCS\Search\IntelligentSearch();
    }
    public function search($searchTerm = NULL)
    {
        return $this->getSearchable()->search($searchTerm);
    }
    public function setAutoSearch(\WHMCS\Http\Message\ServerRequest $request)
    {
        $status = $request->get("autosearch");
        \WHMCS\Search\IntelligentSearchAutoSearch::setStatus($status === "true");
        return new \WHMCS\Http\JsonResponse(array("success" => true));
    }
}

?>