<!--[if lt IE 9]>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.min.js"></script>
<![endif]-->

<!-- RangeSlider CSS -->
<link type="text/css" rel="stylesheet" href="{$BASE_PATH_CSS}/ion.rangeSlider.css" property="stylesheet" />
<!-- RangeSlider CSS -->
<link type="text/css" rel="stylesheet" href="{$BASE_PATH_CSS}/ion.rangeSlider.skinHTML5.css" property="stylesheet" />
<!-- Core CSS -->
<link type="text/css" rel="stylesheet" href="{$WEB_ROOT}/templates/orderforms/{$carttpl}/css/style.css" property="stylesheet" />

<script>
jQuery(document).ready(function () {
    jQuery('#btnShowSidebar').click(function () {
        if (jQuery(".product-selection-sidebar").is(":visible")) {
            jQuery('.row-product-selection').css('left','0');
            jQuery('.product-selection-sidebar').fadeOut();
            jQuery('#btnShowSidebar').html('<i class="fas fa-arrow-circle-right"></i> {$LANG.showMenu}');
        } else {
            jQuery('.product-selection-sidebar').fadeIn();
            jQuery('.row-product-selection').css('left','300px');
            jQuery('#btnShowSidebar').html('<i class="fas fa-arrow-circle-left"></i> {$LANG.hideMenu}');
        }
    });
});
</script>

{if $errormessage}
    <div class="alert alert-danger">
        {$errormessage}
    </div>
{else}

    <div class="row row-product-selection">
        <div class="col-xs-3 product-selection-sidebar" id="premiumComparisonSidebar">
            {include file="orderforms/standard_cart/sidebar-categories.tpl"}
        </div>
        <div class="col-xs-12">

            <div id="order-cloud_slider">
                <section class="plans-full-main">
                    {if $showSidebarToggle}
                        <div class="pull-left">
                            <button type="button" class="btn btn-default btn-sm" id="btnShowSidebar">
                                <i class="fas fa-arrow-circle-right"></i>
                                {$LANG.showMenu}
                            </button>
                        </div>
                    {/if}
                    <div class="main-container">

                        <div class="pg-cont-container">

                            <div class="heading-with-cloud">
                                <div id="headline" class="texts-container">
                                    {if $productGroup.headline}
                                        {$productGroup.headline}
                                    {else}
                                        {$productGroup.name}
                                    {/if}
                                </div>
                                <div class="images-container">
                                    <img src="{$WEB_ROOT}/templates/orderforms/{$carttpl}/img/sky-hr.png" alt="">
                                </div>
                            </div>

                            {if $productGroup.tagline}
                                <div id="tagline" class="tag-line-head">
                                    <h5>{$productGroup.tagline}</h5>
                                </div>
                            {/if}

                            <!-- Start: Price Calculation Box -->
                            <div class="price-calc-container">
                                <div class="price-calc-top">
                                    <div class="row clearfix">
                                        <div class="col-md-9" id="products-top">
                                            <input type="hidden" id="scroll-top" name="scroll-top" value="" />
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <span id="priceTop" class="price-cont">--</span>
                                            <a href="#" class="order-btn" id="product-order-button">
                                                {$LANG.ordernowbutton}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="price-calc-btm">

                                    <!-- Start: Progress Area Container -->
                                    <div id="productFeaturesTop" class="row clearfix">
                                        <!-- Javascript will populate this area with product features. -->
                                    </div>
                                    <!-- End: Progress Area Container -->

                                    <div id="productDescription"></div>

                                    {if count($productGroup.features) > 0}
                                        <!-- Start: Includes Container -->
                                        <div class="includes-container">
                                            <div class="row clearfix">

                                                <div class="col-md-12">
                                                    <div class="head-area">
                                                        <span>
                                                            {$LANG.whatIsIncluded}
                                                        </span>
                                                    </div>

                                                    <ul id="list-contents" class="list-contents">
                                                        {foreach $productGroup.features as $features}
                                                            <li>{$features.feature}</li>
                                                        {/foreach}
                                                    </ul>

                                                </div>

                                            </div>
                                        </div>
                                        <!-- End: Includes Container -->
                                    {/if}
                                </div>
                            </div>
                            <!-- End: Price Calculation Box -->


                            <!-- Start: Features Content -->
                            <div class="price-features-container">
                                <div class="row clearfix">

                                    <!-- Start: Feature 01 -->
                                    <div class="col-md-12 feature-container clearfix">
                                        <div class="left-img">
                                            <img src="{$WEB_ROOT}/templates/orderforms/{$carttpl}/img/feat-img-01.png" alt="">
                                        </div>
                                        <h4>
                                            {$LANG.cloudSlider.feature01Title}
                                        </h4>
                                        <p>
                                            {$LANG.cloudSlider.feature01Description}
                                        </p>
                                        <p>
                                            {$LANG.cloudSlider.feature01DescriptionTwo}
                                        </p>
                                    </div>
                                    <!-- End: Feature 01 -->

                                    <!-- Start: Feature 02 -->
                                    <div class="col-md-12 feature-container clearfix">
                                        <div class="right-img">
                                            <img src="{$WEB_ROOT}/templates/orderforms/{$carttpl}/img/feat-img-02.png" alt="">
                                        </div>
                                        <h4>
                                            {$LANG.cloudSlider.feature02Title}
                                        </h4>
                                        <p>
                                            {$LANG.cloudSlider.feature02Description}
                                        </p>
                                        <p>
                                            {$LANG.cloudSlider.feature02DescriptionTwo}
                                        </p>
                                    </div>
                                    <!-- End: Feature 02 -->

                                    <!-- Start: Feature 03 -->
                                    <div class="col-md-12 feature-container clearfix">
                                        <div class="left-img">
                                            <img src="{$WEB_ROOT}/templates/orderforms/{$carttpl}/img/feat-img-03.jpg" alt="">
                                        </div>
                                        <h4>
                                            {$LANG.cloudSlider.feature03Title}
                                        </h4>
                                        <p>
                                            {$LANG.cloudSlider.feature03Description}
                                        </p>
                                        <p>
                                            {$LANG.cloudSlider.feature03DescriptionTwo}
                                        </p>
                                    </div>
                                    <!-- End: Feature 03 -->

                                </div>
                            </div>
                            <!-- End: Features Content -->

                            <h3 class="text-center">{$LANG.cloudSlider.selectProductLevel}</h3>

                            <!-- Start: Price Calculation Box -->
                            <div class="price-calc-container">
                                <div class="price-calc-top">
                                    <div class="row clearfix">
                                        <div class="col-md-9" id="products-bottom">
                                            <input type="hidden" id="scroll-bottom" name="scroll-bottom" value="" />
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <span id="priceBottom" class="price-cont">--</span>
                                            <a href="#" class="order-btn" id="product-order-button-bottom">
                                                {$LANG.ordernowbutton}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="price-calc-btm">

                                    <!-- Start: Progress Area Container -->
                                    <div id="productFeaturesBottom" class="row clearfix">
                                        <!-- Javascript will populate this area with product features. -->
                                    </div>
                                    <!-- End: Progress Area Container -->
                                </div>
                            </div>
                            <!-- End: Price Calculation Box -->

                        </div>

                    </div>
                </section>

            </div>

        </div>
    </div>
{/if}
<!-- RangeSlider JS -->
<script type="text/javascript" src="{$BASE_PATH_JS}/ion.rangeSlider.js"></script>
<script type="text/javascript">

var sliderActivated = false;

var sliderProductNames = [
    {foreach $products as $product}
        "{$product.name}",
    {/foreach}
];

var allProducts = {
    {foreach $products as $num => $product}
        "{$num}": {
            "name": "{$product.name}",
            "desc": "{$product.featuresdesc|nl2br|trim}",
            {if isset($product.pid)}
                "pid": "{$product.pid}",
                "displayPrice": "{$product.pricing.minprice.price}",
                "displayCycle": "{$product.pricing.minprice.cycle}",
            {else}
                "bid": "{$product.bid}",
                "displayPrice": "{$product.displayprice}",
                "displayCycle": "",
            {/if}
            "features": {
                {foreach $product.features as $k => $feature}
                    "{$k}": "{$feature}",
                {/foreach}
            },
            "featurePercentages": {
                {foreach $featurePercentages as $featureKey => $feature}
                    {if isset($feature.$num)}
                        "{$featureKey}": "{$feature.$num}",
                    {/if}
                {/foreach}
            }
        },
    {/foreach}
};

var definedProducts = {
    {foreach $products as $product}
        "{if isset($product.pid)}{$product.pid}{else}b{$product.bid}{/if}": "{$product@index}"{if !($product@last)},
    {/if}
    {/foreach}
};

{foreach $products as $product}
    {if $product.isFeatured}
        var firstFeatured = definedProducts["{if isset($product.pid)}{$product.pid}{else}b{$product.bid}{/if}"];
        {break}
    {/if}
{/foreach}

var rangeSliderValues = {
    type: "single",
    grid: true,
    grid_snap: true,
    step: 1,
    onStart: updateFeaturesList,
    {if $products|@count eq 1}
        disable: true,
    {/if}
    onChange: updateFeaturesList,
    values: sliderProductNames
};

{if $pid}
    rangeSliderValues['from'] = definedProducts["{$pid}"];
{else}
    if (typeof firstFeatured != 'undefined') {
        rangeSliderValues['from'] = firstFeatured;
    }
{/if}

function updateFeaturesList(data)
{
    var featureName = "";
    var featureMarkup = "";
    var i = parseInt(data.from);
    if (isNaN(i)) {
        i = 0;
        jQuery(".irs-single").text(sliderProductNames[0]);
        jQuery(".irs-grid-text").text('');
    }

    var pid = allProducts[i].pid;
    var bid = allProducts[i].bid;
    var desc = allProducts[i].desc;
    var features = allProducts[i].features;
    var featurePercentages = allProducts[i].featurePercentages;
    var displayCycle = '<br><small>' + allProducts[i].displayCycle + '</small>';
    var displayPrice = allProducts[i].displayPrice + displayCycle;

    var selectedId = data.input[0].id;
    var featuresTargetArea = "";
    var priceTargetArea = "";
    var orderNowArea = "";
    var selfLink = "{$smarty.server.PHP_SELF}";
    var buyLink = "";

    if (selectedId == 'scroll-top') {
        if (sliderActivated) {
            jQuery("#scroll-bottom").data("ionRangeSlider").update({
               from:i
            });
        }
    } else {
        if (sliderActivated) {
            jQuery("#scroll-top").data("ionRangeSlider").update({
                from:i
            });
        }
    }

    // Create the Order Now link.
    if (typeof pid !== "undefined") {
        buyLink = selfLink + "?a=add&pid=" + pid;
    } else {
        buyLink = selfLink + "?a=add&bid=" + bid;
    }

    // Clear the description.
    jQuery("#productFeaturesTop").empty();
    jQuery("#productFeaturesBottom").empty();

    // Update the displayed price.
    jQuery("#priceTop").html(displayPrice);
    jQuery("#priceBottom").html(displayPrice);

    // Update the href for the Order Now button.
    jQuery("#product-order-button").attr("href", buyLink);
    jQuery("#product-order-button-bottom").attr("href", buyLink);

    for (featureName in features) {
        featureMarkup = '<div class="col-md-3 container-with-progress-bar">' +
                            featureName +
                            '<span>' + features[featureName] + '</span>' +
                            '<div class="progress small-progress">' +
                                '<div class="progress-bar" role="progressbar" aria-valuenow="'+ featurePercentages[featureName] + '" aria-valuemin="0" aria-valuemax="100" style="width: ' + featurePercentages[featureName] + '%;">' +
                                    '<span class="sr-only">' + featurePercentages[featureName] + '% Complete</span>' +
                                '</div>' +
                            '</div>' +
                        '</div>';

        jQuery("#productFeaturesTop").append(featureMarkup);
        jQuery("#productFeaturesBottom").append(featureMarkup);
    }

    jQuery("#productDescription").html(desc);
}

jQuery("#scroll-top").ionRangeSlider(rangeSliderValues);
jQuery("#scroll-bottom").ionRangeSlider(rangeSliderValues);
{if $products|@count eq 1}
    jQuery(".irs-single").text(sliderProductNames[0]);
    jQuery(".irs-grid-text").text('');
{/if}

sliderActivated = true;
</script>

