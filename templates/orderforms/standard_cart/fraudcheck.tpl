{include file="orderforms/standard_cart/common.tpl"}

<div id="order-standard_cart">

    <div class="header-lined">
        <h1>
            {$LANG.cartfraudcheck}
        </h1>
    </div>

    <div class="row">

        <div class="col-md-10 col-md-offset-1">

            {include file="orderforms/standard_cart/sidebar-categories-collapsed.tpl"}

            <div class="alert alert-danger error-heading">
                <i class="fas fa-exclamation-triangle"></i>
                {$errortitle}
            </div>

            <div class="row">
                <div class="col-sm-8 col-sm-offset-2 text-center">

                    <p class="margin-bottom">{$error}</p>

                    <p>
                        <a href="submitticket.php" class="btn btn-default">
                            {$LANG.orderForm.submitTicket}
                            &nbsp;<i class="fas fa-arrow-right"></i>
                        </a>
                    </p>

                </div>
            </div>

        </div>
    </div>
</div>
