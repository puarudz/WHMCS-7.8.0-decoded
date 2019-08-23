<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/style.css" />

<div id="order-boxes">

    <div class="header-lined">
        <h1>{$LANG.orderconfirmation}</h1>
    </div>

    <p>{$LANG.orderreceived}</p>

    <div class="row">
        <div class="col-sm-8 col-sm-offset-2 col-md-6 col-md-offset-3">
            <div class="alert alert-success text-center large-text" role="alert">
                {$LANG.ordernumberis} <strong>{$ordernumber}</strong>
            </div>
        </div>
    </div>

    <p>{$LANG.orderfinalinstructions}</p>

    {if $invoiceid && !$ispaid}
        <div class="alert alert-danger" role="alert">
            {$LANG.ordercompletebutnotpaid}
        </div>
        <div class="line-padded text-center">
            <a href="viewinvoice.php?id={$invoiceid}" target="_blank" class="btn btn-success">{$LANG.invoicenumber}{$invoiceid}</a>
        </div>
    {/if}

    {foreach from=$addons_html item=addon_html}
        <div class="line-padded">
            {$addon_html}
        </div>
    {/foreach}

    {if $ispaid}
        <!-- Enter any HTML code which needs to be displayed once a user has completed the checkout of their order here - for example conversion tracking and affiliate tracking scripts -->
    {/if}

    <div class="line-padded text-center">
        <a href="clientarea.php" class="btn btn-primary btn-lg">{$LANG.ordergotoclientarea}</a>
    </div>

</div>
