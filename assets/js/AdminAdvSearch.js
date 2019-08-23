function toggleadvsearch() {
    if (document.getElementById('searchbox').style.visibility=="hidden") {
        document.getElementById('searchbox').style.visibility="";
    } else {
        document.getElementById('searchbox').style.visibility="hidden";
    }
}

function populate(o) {
  d=document.getElementById('searchfield');
  v=o.options[o.selectedIndex].value;
  if(!d){return;}            
  var mitems=new Array();
  mitems['clients']=['Client ID','Client Name','Company Name','Email Address','Address 1','Address 2','City','State','Postcode','Country','Phone Number','CC Last Four','Notes'];
  mitems['orders']=['Order ID','Order #','Client Name','Order Date','Amount'];
  mitems['services']=['Service ID','Domain','Client Name','Product','Billing Cycle','Next Due Date','Status','Username','Dedicated IP','Assigned IPs','Subscription ID','Notes'];
  mitems['domains']=['Domain ID','Domain','Client Name','Registrar','Expiry Date','Status','Subscription ID','Notes'];
  mitems['invoices']=['Invoice #','Client Name','Line Item','Invoice Date','Due Date','Date Paid','Total Due','Status'];
  mitems['tickets']=['Ticket #','Tag','Subject','Client Name','Email Address'];
  d.options.length=0;
  cur=mitems[o.options[o.selectedIndex].value];
  if(!cur){return;}
  d.options.length=cur.length;
  for(var i=0;i<cur.length;i++) {
    d.options[i].text=cur[i];
    d.options[i].value=cur[i];
  }
  if(v == 'services' || v == 'domains' || v == "clients") { 
    document.getElementById('searchfield').selectedIndex = 1;
  }
}