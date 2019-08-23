{if !empty($maintenancemode)}
    <div class="errorbox" style="font-size:14px;">
        {$_ADMINLANG.home.maintenancemode}
    </div>
    <br />
{/if}

{$infobox}

{foreach from=$addons_html item=addon_html}
    <div class="addon-html-output-container">
        {$addon_html}
    </div>
{/foreach}

<div id="widgetSettingsDropdown" class="btn-group widget-settings pull-right">
    <button type="button" class="btn btn-link" id="widgetSettings" data-toggle="dropdown" data-placement="bottom" aria-haspopup="true" aria-expanded="false">
        <i class="fab fa-whmcs" aria-hidden="true"></i>
        <span class="sr-only">{lang key='global.settings'}</span>
    </button>
    <ul id="widgetSettingsDropdownMenu" class="dropdown-menu pull-right" aria-labelledby="widgetSettings">
        <li>
            <h4>{lang key='home.configureWidgetDisplayTitle'}</h4>
        </li>
        {foreach $widgets as $widget}
            <li{if !in_array($widget->getId(), $hiddenWidgets)} class="active"{/if}>
                <label class="checkbox-inline">
                    <input type="checkbox" class="display-widget"{if !in_array($widget->getId(), $hiddenWidgets)} checked="checked"{/if} data-widget="{$widget->getId()}" value="1">
                    {$widget->getTitle()}
                </label>
            </li>
        {/foreach}
    </ul>

</div>

{foreach $staticWidgets as $widget}
    <div id="panel{$widget->getId()}" class="dashboard-panel-static-item dashboard-panel-item-columns-{$widget->getColumnSize()}{if in_array($widget->getId(), $hiddenWidgets)} hidden{/if}">
        {if $widget->showWrapper()}
        <div class="panel panel-default widget-{$widget->getId()|strtolower}" data-widget="{$widget->getId()}">
            <div class="panel-heading">
                <div class="widget-tools">
                    <a href="#" class="widget-refresh"><i class="fas fa-sync"></i></a>
                    <a href="#" class="widget-minimise"><i class="fas fa-chevron-up"></i></a>
                    <a href="#" class="widget-hide"><i class="fas fa-times"></i></a>
                </div>
                <h3 class="panel-title">{$widget->getTitle()}</h3>
            </div>
            <div class="panel-body">
                {/if}
                {$widget->render()}
                {if $widget->showWrapper()}
            </div>
        </div>
        {/if}
    </div>
{/foreach}

<div class="home-widgets-container" data-masonry='{ "itemSelector": ".dashboard-panel-item", "columnWidth": ".dashboard-panel-sizer", "percentPosition": "true" }'>
    <div class="dashboard-panel-sizer"></div>

    {foreach $sortableWidgets as $widget}
        <div id="panel{$widget->getId()}" data-widget="{$widget->getId()}" class="dashboard-panel-item dashboard-panel-item-columns-{$widget->getColumnSize()}{if in_array($widget->getId(), $hiddenWidgets)} hidden{/if}">
            {if $widget->showWrapper()}
                <div class="panel panel-default widget-{$widget->getId()|strtolower}" data-widget="{$widget->getId()}">
                    <div class="panel-heading">
                        <div class="widget-tools">
                            <a href="#" class="widget-refresh"><i class="fas fa-sync"></i></a>
                            <a href="#" class="widget-minimise"><i class="fas fa-chevron-up"></i></a>
                            <a href="#" class="widget-hide"><i class="fas fa-times"></i></a>
                        </div>
                        <h3 class="panel-title">{$widget->getTitle()}</h3>
                    </div>
                    <div class="panel-body">
            {/if}

            {$widget->render()}

            {if $widget->showWrapper()}
                    </div>
                </div>
            {/if}
        </div>
    {/foreach}
</div>

{$generateInvoices}
{$creditCardCapture}
