<script>
    $(document).ready(function () {
        $('.feature-highlights-carousel').owlCarousel({
            items: 1,
            loop: true,
            center: true,
            mouseDrag: true,
            touchDrag: true,
            autoplay: true,
            autoplayTimeout: 4000,
            autoplayHoverPause: true
        });

        setTimeout(function() { $('.feature-highlights-carousel .feature').removeClass('hidden'); }, 500);

        var dismissedForAdmin = parseInt('{$dismissedForAdmin}');

        if (dismissedForAdmin) {
            $('#cbFeatureHighlightsDismissForVersion').attr('checked', true);
        }
    });
</script>

<div class="feature-highlights-content">
    <div class="feature-highlights-carousel owl-carousel owl-theme">
        {foreach $features as $featureId => $feature}
            <div class="feature{if $featureId > 0} hidden{/if}" id="featureHighlight{$featureId}">
                <div class="icon-image">
                    <img src="{$feature->getIcon()}">
                </div>
                <h1{if $feature->hasHeadlineImage()} class="with-headline"{/if}>{$feature->getTitle()}</h1>
                {if $feature->hasHeadlineImage()}
                    <img src="{$feature->getHeadlineImage()}" class="headline-image">
                {/if}
                <h2>{$feature->getSubtitle()}</h2>
                <div class="feature-text">
                    {$feature->getDescription()}
                </div>
                <div class="action-btns">
                    <div class="row">
                        {if $feature->hasBtn1Link()}
                            <div class="col-sm-6{if !$feature->hasBtn2Link()} col-sm-offset-3{/if}">
                                <a href="{$feature->getBtn1Link()}" class="btn btn-block btn-action-1" target="_blank" data-link="1" data-link-title="{$feature@iteration}">
                                    {$feature->getBtn1Label()}
                                </a>
                            </div>
                        {/if}
                        {if $feature->hasBtn2Link()}
                            <div class="col-sm-6">
                                <a href="{$feature->getBtn2Link()}" class="btn btn-block btn-action-2" target="_blank" data-link="2" data-link-title="{$feature@iteration}">
                                    {$feature->getBtn2Label()}
                                </a>
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
        {/foreach}
    </div>
</div>

<label class="checkbox-inline dismiss">
    <input type="checkbox" id="cbFeatureHighlightsDismissForVersion">
    Don't show this again until next update
</label>
