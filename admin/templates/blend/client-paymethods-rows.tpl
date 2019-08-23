{foreach from=$payMethods key=$i item=$payMethod}
    <tr class="{if $i % 2}altrow{/if}">
        <td class="client-paymethod{if $payMethod.isUsingInactiveGateway} gateway-inactive{/if}">
            <a id="btnPayMethodDetails{$payMethod.id}"
               href="{$payMethod.url}"
               data-modal-title="Pay Method Details"
               data-btn-submit-id="savePaymentMethod"
               data-btn-submit-label="{AdminLang::trans('global.savechanges')}"
               data-role="edit-paymethod"
               onclick="return false;"
               {if $payMethod.isUsingInactiveGateway}
               title="{AdminLang::trans('clientsummary.payMethodGatewayInactive')}"
               {/if}
               class="paymethod-description open-modal">
                <i class="{$payMethod.iconClass}"></i>
                &nbsp;&nbsp;{$payMethod.description}
                {if $payMethod.isDefault}<i class="pull-right fal fa-user-check">&nbsp;&nbsp;</i>{/if}
            </a>
        </td>
    </tr>
    {foreachelse}
    <tr>
        <td align="center">No Pay Methods</td>
    </tr>
{/foreach}
