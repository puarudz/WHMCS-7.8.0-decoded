<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Apps;

class AppsController
{
    public function index(\WHMCS\Http\Message\ServerRequest $request, $postLoadAction = NULL, $postLoadParams = array())
    {
        $aInt = new \WHMCS\Admin("Apps and Integrations");
        $aInt->setResponseType(\WHMCS\Admin::RESPONSE_HTML_MESSAGE);
        $aInt->title = \AdminLang::trans("apps.title");
        $aInt->sidebar = "apps";
        $aInt->icon = "apps";
        try {
            $aInt->content = view("admin.apps.index", array("assetHelper" => \DI::make("asset"), "heros" => (new \WHMCS\Apps\Hero\Collection())->get(), "postLoadAction" => $postLoadAction, "postLoadParams" => $postLoadParams));
        } catch (\WHMCS\Exception\Http\ConnectionError $e) {
            $aInt->content = view("admin.apps.index", array("assetHelper" => \DI::make("asset"), "connectionError" => true));
        } catch (\WHMCS\Exception $e) {
            $aInt->content = view("admin.apps.index", array("assetHelper" => \DI::make("asset"), "renderError" => $e->getMessage()));
        }
        return $aInt->display();
    }
    public function jumpBrowse(\WHMCS\Http\Message\ServerRequest $request)
    {
        return $this->index($request, "browse", array("category" => $request->get("category")));
    }
    public function jumpActive(\WHMCS\Http\Message\ServerRequest $request)
    {
        return $this->index($request, "active");
    }
    public function jumpSearch(\WHMCS\Http\Message\ServerRequest $request)
    {
        return $this->index($request, "search");
    }
    public function featured(\WHMCS\Http\Message\ServerRequest $request)
    {
        return new \WHMCS\Http\Message\JsonResponse(array("content" => view("admin.apps.featured", array("apps" => new \WHMCS\Apps\App\Collection(), "categories" => new \WHMCS\Apps\Category\Collection()))));
    }
    public function active(\WHMCS\Http\Message\ServerRequest $request)
    {
        return new \WHMCS\Http\Message\JsonResponse(array("content" => view("admin.apps.active", array("apps" => new \WHMCS\Apps\App\Collection()))));
    }
    public function search(\WHMCS\Http\Message\ServerRequest $request)
    {
        return new \WHMCS\Http\Message\JsonResponse(array("content" => view("admin.apps.response.search", array("apps" => new \WHMCS\Apps\App\Collection()))));
    }
    public function category(\WHMCS\Http\Message\ServerRequest $request)
    {
        $slug = $request->get("category");
        $apps = new \WHMCS\Apps\App\Collection();
        $categories = new \WHMCS\Apps\Category\Collection();
        $category = $categories->getCategoryBySlug($slug);
        if (is_null($category)) {
            $category = $categories->first();
        }
        return new \WHMCS\Http\Message\JsonResponse(array("displayname" => $category->getDisplayName(), "content" => view("admin.apps.category", array("apps" => $apps, "category" => $category, "categories" => $categories))));
    }
    public function infoModal(\WHMCS\Http\Message\ServerRequest $request)
    {
        $moduleSlug = $request->get("moduleSlug");
        $apps = new \WHMCS\Apps\App\Collection();
        if (!$apps->exists($moduleSlug)) {
            return new \WHMCS\Http\Message\JsonResponse(array("body" => view("admin.apps.modal.error", array("errorMsg" => "Module not found. Please try again."))));
        }
        return new \WHMCS\Http\Message\JsonResponse(array("body" => view("admin.apps.modal.info", array("app" => $apps->get($moduleSlug)))));
    }
    public function logo(\WHMCS\Http\Message\ServerRequest $request)
    {
        $moduleSlug = $request->get("moduleSlug");
        $app = (new \WHMCS\Apps\App\Collection())->get($moduleSlug);
        header("Content-type:image/png");
        return $app->getLogoContent();
    }
}

?>