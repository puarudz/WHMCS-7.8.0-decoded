<div id="clientsummarycontainer">

    <div class="clearfix">

        <div class="clientsummaryactions">
            {$_ADMINLANG.clientsummary.settingtaxexempt}: <span id="taxstatus" class="csajaxtoggle" style="text-decoration:underline;cursor:pointer"><strong class="{if $clientsdetails.taxstatus == "Yes"}textgreen{else}textred{/if}">{$clientsdetails.taxstatus}</strong></span>
            &nbsp;&nbsp;
            {$_ADMINLANG.clientsummary.settingautocc}: <span id="autocc" class="csajaxtoggle" style="text-decoration:underline;cursor:pointer"><strong class="{if $clientsdetails.autocc == "Yes"}textgreen{else}textred{/if}">{$clientsdetails.autocc}</strong></span>
            &nbsp;&nbsp;
            {$_ADMINLANG.clientsummary.settingreminders}: <span id="overduenotices" class="csajaxtoggle" style="text-decoration:underline;cursor:pointer"><strong class="{if $clientsdetails.overduenotices == "Yes"}textgreen{else}textred{/if}">{$clientsdetails.overduenotices}</strong></span>
            &nbsp;&nbsp;
            {$_ADMINLANG.clientsummary.settinglatefees}: <span id="latefees" class="csajaxtoggle" style="text-decoration:underline;cursor:pointer"><strong class="{if $clientsdetails.latefees == "Yes"}textgreen{else}textred{/if}">{$clientsdetails.latefees}</strong></span>
        </div>

        <div class="client-summary-name">
            #<span id="userId">{$clientsdetails.userid}</span> - {$clientsdetails.firstname} {$clientsdetails.lastname}
        </div>

        {if $emailVerificationEnabled && $emailVerificationPending}
            <div class="email-verification alert-warning" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                &nbsp;
                {$_ADMINLANG.global.emailAddressNotVerified}
                <div class="pull-right">
                    <button id="btnResendVerificationEmail" class="btn btn-default btn-sm">
                        {$_ADMINLANG.global.resendEmail}
                    </button>
                </div>
            </div>
        {/if}

        {foreach from=$addons_html item=addon_html}
            <div class="addon-html-output-container">
                {$addon_html}
            </div>
        {/foreach}

    </div>

    <div class="row client-summary-panels">
        <div class="col-lg-3 col-sm-6">

            <div class="clientssummarybox">
                <div class="title">{$_ADMINLANG.clientsummary.infoheading}</div>
                <table class="clientssummarystats" cellspacing="0" cellpadding="2">
                    <tr><td width="110">{$_ADMINLANG.fields.firstname}</td><td>{$clientsdetails.firstname}</td></tr>
                    <tr class="altrow"><td>{$_ADMINLANG.fields.lastname}</td><td>{$clientsdetails.lastname}</td></tr>
                    <tr><td>{$_ADMINLANG.fields.companyname}</td><td>{$clientsdetails.companyname}</td></tr>
                    <tr class="altrow"><td>{$_ADMINLANG.fields.email}</td><td>{$clientsdetails.email}{if $emailVerificationEnabled} {if $emailVerified}<span class="label label-success">{$_ADMINLANG.clients.emailVerified}</span>{else}<span class="label label-danger">{$_ADMINLANG.clients.emailUnverified}</span>{/if}{/if}</td></tr>
                    <tr><td>{$_ADMINLANG.fields.address1}</td><td>{$clientsdetails.address1}</td></tr>
                    <tr class="altrow"><td>{$_ADMINLANG.fields.address2}</td><td>{$clientsdetails.address2}</td></tr>
                    <tr><td>{$_ADMINLANG.fields.city}</td><td>{$clientsdetails.city}</td></tr>
                    <tr class="altrow"><td>{$_ADMINLANG.fields.state}</td><td>{$clientsdetails.state}</td></tr>
                    <tr><td>{$_ADMINLANG.fields.postcode}</td><td>{$clientsdetails.postcode}</td></tr>
                    <tr class="altrow"><td>{$_ADMINLANG.fields.country}</td><td>{$clientsdetails.country} - {$clientsdetails.countrylong}</td></tr>
                    <tr><td>{$_ADMINLANG.fields.phonenumber}</td><td>{$clientsdetails.phonenumber}</td></tr>
                    {if $showTaxIdField}
                        <tr class="altrow">
                            <td>{lang key=\WHMCS\Billing\Tax\Vat::getLabel('fields')}</td>
                            <td>{$clientsdetails.tax_id}</td>
                        </tr>
                    {/if}
                </table>
                <ul>
                    <li><a id="summary-reset-password" href="clientssummary.php?userid={$clientsdetails.userid}&resetpw=true&token={$csrfToken}"><img src="images/icons/resetpw.png" border="0" align="absmiddle" /> {$_ADMINLANG.clients.resetsendpassword}</a></li>
                    <li><a id="summary-login-as-client" href="../dologin.php?username={$clientsdetails.email|urlencode}&language={$adminLanguage}"><img src="images/icons/clientlogin.png" border="0" align="absmiddle" /> {$_ADMINLANG.clientsummary.loginasclient}</a></li>
                </ul>
            </div>

            <div class="clientssummarybox">
                <div class="title">{$_ADMINLANG.clientsummary.contactsheading}</div>
                <table class="clientssummarystats" cellspacing="0" cellpadding="2">
                {foreach key=num from=$contacts item=contact}
                    <tr class="{cycle values=",altrow"}"><td align="center"><a href="clientscontacts.php?userid={$clientsdetails.userid}&contactid={$contact.id}">{$contact.firstname} {$contact.lastname}</a> - {$contact.email}</td></tr>
                {foreachelse}
                    <tr><td align="center">{$_ADMINLANG.clientsummary.nocontacts}</td></tr>
                {/foreach}
                </table>
                <ul>
                    <li><a href="clientscontacts.php?userid={$clientsdetails.userid}&contactid=addnew"><img src="images/icons/clientsadd.png" border="0" align="absmiddle" /> {$_ADMINLANG.clients.addcontact}</a></li>
                </ul>
            </div>
            {$paymethodsSummary}

        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="clientssummarybox">
                <div class="title">{$_ADMINLANG.clientsummary.billingheading}</div>
                <table class="clientssummarystats" cellspacing="0" cellpadding="2">
                    <tr>
                        <td width="110">{$_ADMINLANG.status.paid}</td>
                        <td>{$stats.numpaidinvoices} ({$stats.paidinvoicesamount})</td>
                    </tr>
                    <tr class="altrow">
                        <td>{$_ADMINLANG.status.draft}</td>
                        <td>{$stats.numDraftInvoices} ({$stats.draftInvoicesBalance})</td>
                    </tr>
                    <tr>
                        <td>{$_ADMINLANG.status.unpaid}/{$_ADMINLANG.status.due}</td>
                        <td>{$stats.numdueinvoices} ({$stats.dueinvoicesbalance})</td>
                    </tr>
                    <tr class="altrow">
                        <td>{$_ADMINLANG.status.cancelled}</td>
                        <td>{$stats.numcancelledinvoices} ({$stats.cancelledinvoicesamount})</td>
                    </tr>
                    <tr>
                        <td>{$_ADMINLANG.status.refunded}</td>
                        <td>{$stats.numrefundedinvoices} ({$stats.refundedinvoicesamount})</td>
                    </tr>
                    <tr class="altrow">
                        <td>{$_ADMINLANG.status.collections}</td>
                        <td>{$stats.numcollectionsinvoices} ({$stats.collectionsinvoicesamount})</td>
                    </tr>
                    <tr>
                        <td colspan="2"><strong>{$_ADMINLANG.billing.income}</strong></td>
                    </tr>
                    <tr class="altrow">
                        <td>{lang key='billing.grossRevenue'}</td>
                        <td>{$stats.grossRevenue}</td>
                    </tr>
                    <tr>
                        <td>{lang key='billing.clientExpenses'}</td>
                        <td>{$stats.expenses}</td>
                    </tr>
                    <tr class="altrow">
                        <td>{lang key='billing.netIncome'}</td>
                        <td><strong>{$stats.income}</strong></td>
                    </tr>
                    <tr>
                        <td>{$_ADMINLANG.clients.creditbalance}</td>
                        <td>{$stats.creditbalance}</td>
                    </tr>
                </table>
                <ul>
                    <li><a href="invoices.php?action=createinvoice&userid={$clientsdetails.userid}&token={$csrfToken}"><img src="images/icons/invoicesedit.png" border="0" align="absmiddle" /> {$_ADMINLANG.invoices.create}</a></li>
                    <li><a href="#" data-toggle="modal" data-target="#modalAddFunds"><img src="images/icons/addfunds.png" border="0" align="absmiddle" /> {$_ADMINLANG.clientsummary.createaddfunds}</a></li>
                    <li><a href="#" data-toggle="modal" data-target="#modalGenerateInvoices"><img src="images/icons/ticketspredefined.png" border="0" align="absmiddle" /> {$_ADMINLANG.invoices.geninvoices}</a></li>
                    <li><a href="clientsbillableitems.php?userid={$clientsdetails.userid}&action=manage"><img src="images/icons/billableitems.png" border="0" align="absmiddle" /> {$_ADMINLANG.billableitems.additem}</a></li>
                    <li><a href="#" id="manageCredits" onClick="window.open('clientscredits.php?userid={$clientsdetails.userid}','','width=800,height=350,scrollbars=yes');return false"><img src="images/icons/income.png" border="0" align="absmiddle" /> {$_ADMINLANG.clientsummary.managecredits}</a></li>
                    <li><a href="quotes.php?action=manage&userid={$clientsdetails.userid}"><img src="images/icons/quotes.png" border="0" align="absmiddle" /> {$_ADMINLANG.quotes.createnew}</a></li>
                </ul>
            </div>

            <div class="clientssummarybox">
                <div class="title">{$_ADMINLANG.clientsummary.otherinfoheading}</div>
                <table class="clientssummarystats" cellspacing="0" cellpadding="2">
                    <tr><td width="110">{$_ADMINLANG.fields.status}</td><td>{$clientsdetails.status}</td></tr>
                    <tr class="altrow"><td>{$_ADMINLANG.fields.clientgroup}</td><td>{$clientgroup.name}</td></tr>
                    <tr><td>{$_ADMINLANG.fields.signupdate}</td><td>{$signupdate}</td></tr>
                    <tr class="altrow"><td>{$_ADMINLANG.clientsummary.clientfor}</td><td>{$clientfor}</td></tr>
                    <tr><td width="110">{$_ADMINLANG.clientsummary.lastlogin}</td><td>{$lastlogin}</td></tr>
                    {if $emailVerificationEnabled}
                    <tr class="altrow"><td>{$_ADMINLANG.fields.emailverified}</td><td>{if $emailVerified}{$_ADMINLANG.global.yes}{else}{$_ADMINLANG.global.no}{/if}</td></tr>
                    {/if}
                </table>
            </div>

        </div>
        <div class="col-lg-3 col-sm-6">

            <div class="clientssummarybox">
                <div class="title">{$_ADMINLANG.services.title}</div>
                <table class="clientssummarystats" cellspacing="0" cellpadding="2">
                    <tr><td width="140">{$_ADMINLANG.orders.sharedhosting}</td><td>{$stats.productsnumactivehosting} ({$stats.productsnumhosting} Total)</td></tr>
                    <tr class="altrow"><td>{$_ADMINLANG.orders.resellerhosting}</td><td>{$stats.productsnumactivereseller} ({$stats.productsnumreseller} Total)</td></tr>
                    <tr><td>{$_ADMINLANG.orders.server}</td><td>{$stats.productsnumactiveservers} ({$stats.productsnumservers} Total)</td></tr>
                    <tr class="altrow"><td>{$_ADMINLANG.orders.other}</td><td>{$stats.productsnumactiveother} ({$stats.productsnumother} Total)</td></tr>
                    <tr><td>{$_ADMINLANG.domains.title}</td><td>{$stats.numactivedomains} ({$stats.numdomains} Total)</td></tr>
                    <tr class="altrow"><td>{$_ADMINLANG.stats.acceptedquotes}</td><td>{$stats.numacceptedquotes} ({$stats.numquotes} Total)</td></tr>
                    <tr><td>{$_ADMINLANG.support.supporttickets}</td><td>{$stats.numactivetickets} ({$stats.numtickets} Total)</td></tr>
                    <tr class="altrow"><td>{$_ADMINLANG.stats.affiliatesignups}</td><td>{$stats.numaffiliatesignups}</td></tr>
                </table>
                <ul>
                    <li><a href="orders.php?clientid={$clientsdetails.userid}"><img src="images/icons/orders.png" border="0" align="absmiddle" /> {$_ADMINLANG.clientsummary.vieworders}</a></li>
                    <li><a href="ordersadd.php?userid={$clientsdetails.userid}"><img src="images/icons/ordersadd.png" border="0" align="absmiddle" /> {$_ADMINLANG.orders.addnew}</a></li>
                </ul>
            </div>

            <div class="clientssummarybox">
                <div class="title">{$_ADMINLANG.clientsummary.filesheading}</div>
                <table class="clientssummarystats" cellspacing="0" cellpadding="2">
                    {foreach key=num from=$files item=file}
                        <tr class="{cycle values=",altrow"}"><td align="center"><a href="../dl.php?type=f&id={$file.id}"><i class="far fa-file"></i> {$file.title}</a> {if $file.adminonly}({$_ADMINLANG.clientsummary.fileadminonly}){/if} <img src="images/icons/delete.png" align="absmiddle" border="0" onClick="deleteFile('{$file.id}')" /></td></tr>
                    {foreachelse}
                        <tr><td align="center">{$_ADMINLANG.clientsummary.nofiles}</td></tr>
                    {/foreach}
                </table>
                <ul>
                    <li><a href="#" id="addfile"><img src="images/icons/add.png" border="0" align="absmiddle" /> {$_ADMINLANG.clientsummary.fileadd}</a></li>
                </ul>
                <div id="addfileform" style="display:none;" class="top-margin-5">
                    <form method="post" action="clientssummary.php?userid={$clientsdetails.userid}&action=uploadfile" enctype="multipart/form-data">
                        <table class="clientssummarystats" cellspacing="0" cellpadding="2">
                            <tr><td width="40">{$_ADMINLANG.clientsummary.filetitle}</td><td class="fieldarea"><input type="text" name="title" style="width:90%" /></td></tr>
                            <tr><td>{$_ADMINLANG.clientsummary.filename}</td><td class="fieldarea"><input type="file" name="uploadfile" style="width:90%" /></td></tr>
                            <tr><td></td><td class="fieldarea"><input type="checkbox" name="adminonly" value="1" /> {$_ADMINLANG.clientsummary.fileadminonly} &nbsp;&nbsp;&nbsp;&nbsp; <input type="submit" value="{$_ADMINLANG.global.submit}" /></td></tr>
                        </table>
                    </form>
                </div>
            </div>

            <div class="clientssummarybox">
                <div class="title">{$_ADMINLANG.clientsummary.emailsheading}</div>
                <table class="clientssummarystats" cellspacing="0" cellpadding="2">
                    {foreach key=num from=$lastfivemail item=email}
                        <tr class="{cycle values=",altrow"}"><td align="center">{$email.date} - <a href="#" onClick="window.open('clientsemails.php?&displaymessage=true&id={$email.id}','','width=650,height=400,scrollbars=yes');return false">{$email.subject}</a></td></tr>
                    {foreachelse}
                        <tr><td align="center">{$_ADMINLANG.clientsummary.noemails}</td></tr>
                    {/foreach}
                </table>
            </div>

        </div>
        <div class="col-lg-3 col-sm-6">

            <div class="clientssummarybox">
                <div class="title">{$_ADMINLANG.clientsummary.actionsheading}</div>
                <ul>
                    {foreach from=$customactionlinks item=customactionlink}
                        <li>{$customactionlink}</li>
                    {/foreach}
                    <li><a href="reports.php?report=client_statement&userid={$clientsdetails.userid}"><img src="images/icons/reports.png" border="0" align="absmiddle" /> {$_ADMINLANG.clientsummary.accountstatement}</a></li>
                    <li><a href="supporttickets.php?action=open&userid={$clientsdetails.userid}"><img src="images/icons/ticketsopen.png" border="0" align="absmiddle" /> {$_ADMINLANG.clientsummary.newticket}</a></li>
                    <li><a href="supporttickets.php?view=any&client={$clientsdetails.userid}"><img src="images/icons/ticketsother.png" border="0" align="absmiddle" /> {$_ADMINLANG.clientsummary.viewtickets}</a></li>
                    <li><a href="{if $affiliateid}affiliates.php?action=edit&id={$affiliateid}{else}clientssummary.php?userid={$clientsdetails.userid}&activateaffiliate=true&token={$csrfToken}{/if}"><img src="images/icons/affiliates.png" border="0" align="absmiddle" /> {if $affiliateid}{$_ADMINLANG.clientsummary.viewaffiliate}{else}{$_ADMINLANG.clientsummary.activateaffiliate}{/if}</a></li>
                    <li><a href="#" onClick="window.open('clientsmerge.php?userid={$clientsdetails.userid}','movewindow','width=500,height=280,top=100,left=100,scrollbars=1');return false"><img src="images/icons/clients.png" border="0" align="absmiddle" /> {$_ADMINLANG.clientsummary.mergeclients}</a></li>
                    <li><a href="#" onClick="closeClient();return false" style="color:#000000;"><img src="images/icons/delete.png" border="0" align="absmiddle" /> {$_ADMINLANG.clientsummary.closeclient}</a></li>
                    <li><a href="#" onClick="deleteClient();return false" style="color:#CC0000;"><img src="images/icons/delete.png" border="0" align="absmiddle" /> {$_ADMINLANG.clientsummary.deleteclient}</a></li>
                    <li>
                        <a href="reports.php?report=client&userid={$clientsdetails.userid}">
                            <img src="images/icons/csvexports.png" border="0" align="absmiddle" />
                            {$_ADMINLANG.clientsummary.export}
                        </a>
                    </li>
                </ul>
            </div>

            <div class="clientssummarybox">
                <div class="title">{$_ADMINLANG.clientsummary.sendemailheading}</div>
                <form action="clientsemails.php?userid={$clientsdetails.userid}&action=send&type=general" method="post">
                    <input type="hidden" name="id" value="{$clientsdetails.userid}">
                    <div align="center">{$messages} <input type="submit" value="{$_ADMINLANG.global.go}" class="button btn btn-default"></div>
                </form>
            </div>

            <div class="clientssummarybox">
                <div class="title">{$_ADMINLANG.fields.adminnotes}</div>
                <form method="post" action="{$smarty.server.PHP_SELF}?userid={$clientsdetails.userid}&action=savenotes">
                    <div align="center">
                        <textarea name="adminnotes" rows="5" class="form-control bottom-margin-5">{$clientsdetails.notes}</textarea>
                        <input type="submit" value="{$_ADMINLANG.global.submit}" class="button btn btn-default" />
                    </div>
                </form>
            </div>

        </div>
    </div>

    <p align="right">
        <button id="btnStatusEnabled" type="button" value="filter" class="btn btn-xs btn-small" onclick="toggleStatusFilter()">
            {$_ADMINLANG.clientsummary.statusfilter}: <span class="on">{lang key='global.on'}</span><span class="off">{lang key='global.off'}</span>
        </button>
    </p>
    <div id="statusfilter">
        <form>
            <div class="checkall">
                <label class="checkbox-inline"><input type="checkbox" id="statusfiltercheckall" checked="checked"/> {$_ADMINLANG.global.checkall}</label>
            </div>
            <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                <tr>
                    <th></th>
                </tr>
    {foreach from=$itemstatuses key=itemstatus item=statuslang}
                <tr>
                    <td><label class="checkbox-inline" style="display:block;"><input type="checkbox" name="statusfilter[]" value="{$itemstatus}" onclick="uncheckCheckAllStatusFilter()" checked="checked" /> {$statuslang}</label></td>
                </tr>
    {/foreach}
                <tr>
                    <th></th>
                </tr>
            </table>
            <div class="applybtn">
                <input type="button" value="{$_ADMINLANG.global.apply}" class="btn btn-xs btn-small btn-primary" onclick="applyStatusFilter();toggleStatusFilter();return false;" />
            </div>
        </form>
    </div>

    <form method="post" action="{$smarty.server.PHP_SELF}?userid={$clientsdetails.userid}&action=massaction">

        <table width="100%" class="form">
            <tr><td colspan="2" class="fieldarea" style="text-align:center;"><strong>{$_ADMINLANG.services.title}</strong></td></tr>
            <tr><td align="center">

                <div class="tablebg">
                    <table class="datatable filterable" width="100%" border="0" cellspacing="1" cellpadding="3">
                        <tr>
                            <th width="20"><input type="checkbox" id="prodsall" /></th>
                            <th>{$_ADMINLANG.fields.id}</th>
                            <th>{$_ADMINLANG.fields.product}</th>
                            <th>{$_ADMINLANG.fields.amount}</th>
                            <th>{$_ADMINLANG.fields.billingcycle}</th>
                            <th>{$_ADMINLANG.fields.signupdate}</th>
                            <th>{$_ADMINLANG.fields.nextduedate}</th>
                            <th>{$_ADMINLANG.fields.status}</th>
                            <th width="20"></th>
                        </tr>
                        {foreach key=num from=$productsummary item=product}
                            <tr>
                                <td><input type="checkbox" name="selproducts[]" value="{$product.id}" class="checkprods" /></td>
                                <td><a href="clientsservices.php?userid={$clientsdetails.userid}&id={$product.id}">{$product.idshort}</a></td>
                                <td style="padding-left:5px;padding-right:5px">{$product.dpackage} - <a href="http://{$product.domain}" target="_blank">{$product.domain}</a></td>
                                <td>{$product.amount}</td>
                                <td>{$product.dbillingcycle}</td>
                                <td>{$product.regdate}</td>
                                <td>{$product.nextduedate}</td>
                                <td class="status" data-filter-value="{$product.domainoriginalstatus}">{$product.domainstatus}</td>
                                <td><a href="clientsservices.php?userid={$clientsdetails.userid}&id={$product.id}"><img src="images/edit.gif" width="16" height="16" border="0" alt="Edit"></a></td>
                            </tr>
                        {foreachelse}
                            <tr>
                                <td colspan="9">{$_ADMINLANG.global.norecordsfound}</td>
                            </tr>
                        {/foreach}
                    </table>
                </div>

            </td></tr>
        </table>

        <table width="100%" class="form">
            <tr><td colspan="2" class="fieldarea" style="text-align:center;"><strong>{$_ADMINLANG.addons.title}</strong></td></tr>
            <tr><td align="center">

                <div class="tablebg">
                    <table class="datatable filterable" width="100%" border="0" cellspacing="1" cellpadding="3">
                        <tr>
                            <th width="20"><input type="checkbox" id="addonsall" /></th>
                            <th>ID</th>
                            <th>{$_ADMINLANG.addons.name}</th>
                            <th>{$_ADMINLANG.fields.amount}</th>
                            <th>{$_ADMINLANG.fields.billingcycle}</th>
                            <th>{$_ADMINLANG.fields.signupdate}</th>
                            <th>{$_ADMINLANG.fields.nextduedate}</th>
                            <th>{$_ADMINLANG.fields.status}</th>
                            <th width="20"></th>
                        </tr>
                        {foreach key=num from=$addonsummary item=addon}
                            <tr>
                                <td><input type="checkbox" name="seladdons[]" value="{$addon.id}" class="checkaddons" /></td>
                                <td><a href="clientsservices.php?userid={$clientsdetails.userid}&id={$addon.serviceid}&aid={$addon.id}">{$addon.idshort}</a></td>
                                <td style="padding-left:5px;padding-right:5px">{$addon.addonname}<br>{$addon.dpackage} - <a href="http://{$addon.domain}" target="_blank">{$addon.domain}</a></td>
                                <td>{$addon.amount}</td>
                                <td>{$addon.dbillingcycle}</td>
                                <td>{$addon.regdate}</td>
                                <td>{$addon.nextduedate}</td>
                                <td class="status" data-filter-value="{$addon.originalstatus}">{$addon.status}</td>
                                <td><a href="clientsservices.php?userid={$clientsdetails.userid}&id={$addon.serviceid}&aid={$addon.id}"><img src="images/edit.gif" width="16" height="16" border="0" alt="Edit"></a></td>
                            </tr>
                        {foreachelse}
                            <tr>
                                <td colspan="9">{$_ADMINLANG.global.norecordsfound}</td>
                            </tr>
                        {/foreach}
                    </table>
                </div>

            </td></tr>
        </table>

        <table width="100%" class="form">
            <tr><td colspan="2" class="fieldarea" style="text-align:center;"><strong>{$_ADMINLANG.domains.title}</strong></td></tr>
            <tr><td align="center">

                <div class="tablebg">
                    <table class="datatable filterable" width="100%" border="0" cellspacing="1" cellpadding="3">
                        <tr>
                            <th width="20"><input type="checkbox" id="domainsall" /></th>
                            <th>{$_ADMINLANG.fields.id}</th>
                            <th>{$_ADMINLANG.fields.domain}</th>
                            <th>{$_ADMINLANG.fields.registrar}</th>
                            <th>{$_ADMINLANG.fields.regdate}</th>
                            <th>{$_ADMINLANG.fields.nextduedate}</th>
                            <th>{$_ADMINLANG.fields.expirydate}</th>
                            <th>{$_ADMINLANG.fields.status}</th>
                            <th width="20"></th>
                        </tr>
                        {foreach key=num from=$domainsummary item=domain}
                            <tr>
                                <td><input type="checkbox" name="seldomains[]" value="{$domain.id}" class="checkdomains" /></td>
                                <td><a href="clientsdomains.php?userid={$clientsdetails.userid}&domainid={$domain.id}">{$domain.idshort}</a></td>
                                <td style="padding-left:5px;padding-right:5px"><a href="http://{$domain.domain}" target="_blank">{$domain.domain}</a></td>
                                <td>{$domain.registrar}</td>
                                <td>{$domain.registrationdate}</td>
                                <td>{$domain.nextduedate}</td>
                                <td>{$domain.expirydate}</td>
                                <td class="status" data-filter-value="{$domain.originalstatus}">{$domain.status}</td>
                                <td><a href="clientsdomains.php?userid={$clientsdetails.userid}&domainid={$domain.id}"><img src="images/edit.gif" width="16" height="16" border="0" alt="Edit"></a></td>
                            </tr>
                        {foreachelse}
                            <tr>
                                <td colspan="9">{$_ADMINLANG.global.norecordsfound}</td>
                            </tr>
                        {/foreach}
                    </table>
                </div>

            </td></tr>
        </table>

        <table width="100%" class="form">
            <tr><td colspan="2" class="fieldarea" style="text-align:center;"><strong>{$_ADMINLANG.clientsummary.currentquotes}</strong></td></tr>
            <tr><td align="center">

                <div class="tablebg">
                    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                        <tr>
                            <th>{$_ADMINLANG.fields.id}</th>
                            <th>{$_ADMINLANG.fields.subject}</th>
                            <th>{$_ADMINLANG.fields.date}</th>
                            <th>{$_ADMINLANG.fields.total}</th>
                            <th>{$_ADMINLANG.fields.validuntil}</th>
                            <th>{$_ADMINLANG.fields.status}</th>
                            <th width="20"></th>
                        </tr>
                        {foreach key=num from=$quotes item=quote}
                            <tr>
                                <td>{$quote.id}</td>
                                <td style="padding-left:5px;padding-right:5px">{$quote.subject}</td>
                                <td>{$quote.datecreated}</td>
                                <td>{$quote.total}</td>
                                <td>{$quote.validuntil}</td>
                                <td>{$quote.stage}</td>
                                <td><a href="quotes.php?action=manage&id={$quote.id}"><img src="images/edit.gif" width="16" height="16" border="0" alt="Edit"></a></td>
                            </tr>
                        {foreachelse}
                            <tr>
                                <td colspan="7">{$_ADMINLANG.global.norecordsfound}</td>
                            </tr>
                        {/foreach}
                    </table>
                </div>

            </td></tr>
        </table>

        <div class="bulk-action-btns">
            {$_ADMINLANG.global.withselected}:
            <button type="submit" name="inv" value="1" class="button btn btn-sm btn-default">
                <i class="fas fa-sync"></i>
                {$_ADMINLANG.clientsummary.invoiceselected}
            </button>
            <button type="submit" name="del" value="1" class="button btn btn-sm btn-danger">
                <i class="fas fa-trash-alt"></i>
                {$_ADMINLANG.clientsummary.deleteselected}
            </button>
        </div>

        <div class="bulk-actions">

            <div class="row">
                <div class="col-sm-6 hidden-lg">
                    <span class="heading">{$_ADMINLANG.global.bulkActions}</span>
                </div>
                <div class="col-lg-3 col-lg-push-9 col-sm-6 text-right">
                    <button id="massUpdateItems" type="button" class="btn btn-link" onclick="$('#bulkUpdateAdvancedOptions').slideToggle()">
                        {$_ADMINLANG.global.showAdvancedOptions}
                    </button>
                    <button type="submit" id="massupdate" name="massupdate" value="1" class="btn btn-default">
                        {$_ADMINLANG.global.apply}
                    </button>
                </div>
                <div class="col-lg-9 col-lg-pull-3 col-xs-12">
                    <span class="heading visible-lg-inline-block">{$_ADMINLANG.global.bulkActions}</span>
                    <select name="status" class="form-control select-inline">
                        <option value="">- {$_ADMINLANG.support.setStatus} -</option>
                        <option value="Pending">{$_ADMINLANG.status.pending}</option>
                        <option value="Active">{$_ADMINLANG.status.active}</option>
                        <option value="Suspended">{$_ADMINLANG.status.suspended}</option>
                        <option value="Terminated">{$_ADMINLANG.status.terminated}</option>
                        <option value="Cancelled">{$_ADMINLANG.status.cancelled}</option>
                        <option value="Fraud">{$_ADMINLANG.status.fraud}</option>
                    </select>
                    {$paymentmethoddropdown|replace:$_ADMINLANG.global.nochange:$_ADMINLANG.clientsummary.setPaymentMethod}
                    <span>
                        <label class="checkbox-inline">
                            <input type="checkbox" name="overideautosuspend" id="overridesuspend" />
                            {$_ADMINLANG.services.nosuspenduntil}
                            <input type="text" name="overidesuspenduntil" class="input-inline form-control date-picker-single future" data-drops="up" data-original-value="" value="" />
                        </label>
                    </span>
                </div>
            </div>


            <div id="bulkUpdateAdvancedOptions" class="advanced-options">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td width="15%" class="fieldlabel" nowrap>
                            {$_ADMINLANG.fields.firstpaymentamount}
                        </td>
                        <td class="fieldarea" width="35%">
                            <input type="text" name="firstpaymentamount" class="form-control input-200" />
                        </td>
                        <td width="15%" class="fieldlabel" nowrap>
                            {$_ADMINLANG.fields.recurringamount}
                        </td>
                        <td class="fieldarea">
                            <input type="text" name="recurringamount" class="form-control input-200" />
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel" width="15%">
                            {$_ADMINLANG.fields.nextduedate}
                        </td>
                        <td class="fieldarea">
                            <input type="text" id="nextDueDate" name="nextduedate" class="input-inline form-control date-picker-single future" data-drops="up" data-original-value="" value="" /> &nbsp;&nbsp; <input type="checkbox" name="proratabill" id="proratabill" /> {$_ADMINLANG.clientsummary.createproratainvoice}
                        </td>
                        <td width="15%" class="fieldlabel">
                            {$_ADMINLANG.fields.billingcycle}
                        </td>
                        <td class="fieldarea">
                            <select name="billingcycle" class="form-control input-200">
                                <option value="">- {$_ADMINLANG.global.nochange} -</option>
                                <option value="Free Account">{$_ADMINLANG.billingcycles.free}</option>
                                <option value="One Time">{$_ADMINLANG.billingcycles.onetime}</option>
                                <option value="Monthly">{$_ADMINLANG.billingcycles.monthly}</option>
                                <option value="Quarterly">{$_ADMINLANG.billingcycles.quarterly}</option>
                                <option value="Semi-Annually">{$_ADMINLANG.billingcycles.semiannually}</option>
                                <option value="Annually">{$_ADMINLANG.billingcycles.annually}</option>
                                <option value="Biennially">{$_ADMINLANG.billingcycles.biennially}</option>
                                <option value="Triennially">{$_ADMINLANG.billingcycles.triennially}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel" width="15%">
                            {$_ADMINLANG.services.modulecommands}
                        </td>
                        <td class="fieldarea" colspan="3">
                            <input type="submit" name="masscreate" value="{$_ADMINLANG.modulebuttons.create}" class="button btn btn-default" />
                            <input type="submit" name="masssuspend" value="{$_ADMINLANG.modulebuttons.suspend}" class="button btn btn-default" />
                            <input type="submit" name="massunsuspend" value="{$_ADMINLANG.modulebuttons.unsuspend}" class="button btn btn-default" />
                            <input type="submit" name="massterminate" value="{$_ADMINLANG.modulebuttons.terminate}" class="button btn btn-default" />
                            <input type="submit" name="masschangepackage" value="{$_ADMINLANG.modulebuttons.changepackage}" class="button btn btn-default" />
                            <input type="submit" name="masschangepw" value="{$_ADMINLANG.modulebuttons.changepassword}" class="button btn btn-default" />
                        </td>
                    </tr>
                </table>
            </div>

        </div>

    </form>

</div>
