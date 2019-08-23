<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
$GATEWAYMODULE = array("moipapiname" => "moipapi", "moipapivisiblename" => "Pagamento Moip 4.1", "moipapitype" => "Invoices");
function moipapi_config()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "Modulo Moip"), "emailpd" => array("FriendlyName" => "ID*", "Type" => "text", "Size" => "30", "Name" => "emailpd", "Description" => "ID Usuário cadastrado com o Moip"), "taxa_porc" => array("FriendlyName" => "Taxa %", "Type" => "text", "Size" => "5", "Name" => "taxa_porc", "Description" => "Taxa adicional em porcentagem"), "taxa_reais" => array("FriendlyName" => "Taxa Reais", "Type" => "text", "Size" => "5", "Name" => "taxa_reais", "Description" => "Taxa adicional em reais"), "layout" => array("FriendlyName" => "Layout", "Type" => "text", "Size" => "20", "Description" => "Insira o nome do layout criado em sua conta Moip, caso nao haja essa informação o layout utilizado sera o defalt (padrão) Moip.\n<br>Para criar ou alterar um layout acesse sua conta Moip no menu \"Meus Dados\" >> \" Preferencias \" >> \" Layout personalizado da pagina de pagamento \"."), "inst_boleto1" => array("FriendlyName" => "Instrução boleto #1", "Type" => "text", "Size" => "20", "Description" => "Digite aqui a instrução que deseja que seja informada na primeira linha do boleto EX: Senhor caixa, não receber após o vencimento."), "inst_boleto2" => array("FriendlyName" => "Instrução boleto #2", "Type" => "text", "Size" => "20", "Description" => "Digite aqui a instrução que deseja que seja informada na segunda linha do boleto EX: Senhor caixa, não receber após o vencimento."), "inst_boleto3" => array("FriendlyName" => "Instrução boleto #3", "Type" => "text", "Size" => "20", "Description" => "Digite aqui a instrução que deseja que seja informada na terceira linha do boleto EX: Senhor caixa, não receber após o vencimento."), "obs" => array("FriendlyName" => "Observações", "Type" => "textarea", "Size" => "3", "Description" => "Insira um texto na fatura, Ex: Aceitamos Apenas Boleto..."), "url_image" => array("FriendlyName" => "Url da imagem*", "Type" => "text", "Size" => "50", "Description" => "Insira aqui a url da imagem que deseja que seja exibida no lugar do botão pagar agora."), "tp_venc" => array("FriendlyName" => "Tipo vencimento Dias", "Type" => "dropdown", "Options" => "Corridos,Uteis", "Description" => "Digite a forma como deve ser gerado o vencimento em dias úteis ou corridos."), "prz_venc" => array("FriendlyName" => "Prazo vencimento Dias", "Type" => "text", "Size" => "2", "Description" => "Digite O prazo para vencimento em dias."), "func_dbg" => array("FriendlyName" => "Ativar Debug", "Type" => "dropdown", "Options" => "Não,Sim", "Description" => "Habilita função debug."), "habimage" => array("FriendlyName" => "Habilita Imagem", "Type" => "yesno", "Value" => "", "Description" => "Habilita trocar o botão pagar agora por uma imagem."), "habredir" => array("FriendlyName" => "Habilita Redirecionamento", "Type" => "yesno", "Value" => "", "Description" => "Habilita direcionar o cliente direto ao pagamento ao invés de exibir a fatura, evitando assim a necessidade do botão pagar agora"), "capestado" => array("FriendlyName" => "Captura estado", "Type" => "yesno", "Value" => "", "Description" => "Habilita capturar estado do cadastro do cliente e converter em sigla, pra isso o cadastro deve estar correto!."));
    return $configarray;
}
function moipapi_activate()
{
    $username = full_query("SELECT username FROM tbladmins WHERE id='1'");
    $username = mysql_fetch_array($username);
    $username = $username["username"];
    defineGatewayField("moipapi", "text", "username", (string) $username, "Usuário do Sistema", "30", "Nome do usuário administrador");
    defineGatewayField("moipapi", "text", "emailpd", "", "ID*", "30", "ID Usuário cadastrado no Pagamento Moip");
    defineGatewayField("moipapi", "textarea", "obs", "", "Observações", "10", "Insira um texto na fatura, Ex: Aceitamos Apenas Boleto...");
    defineGatewayField("moipapi", "text", "taxa_porc", "0.00", "Taxa %", "5", "Taxa adicional em porcentagem");
    defineGatewayField("moipapi", "text", "taxa_reais", "0.00", "Taxa R\$", "6", "Taxa adicional em Reais");
    defineGatewayField("moipapi", "text", "Layout", "", "Layout", "20", "Insira o nome do layout criado em sua conta Moip, caso nao haja essa informacao o layout utilizado sera o defalt (padrao) Moip.<br>Para criar ou alterar um layout acesse sua conta Moip no menu Meus Dados >>  Preferencias  >>  Layout personalizado da pagina de pagamento");
    defineGatewayField("moipapi", "text", "url_retorno", "", "Url de retorno", "20", "Insira aqui o endereço de onde está instalado o seu whmcs");
    defineGatewayField("moipapi", "text", "url_image", "", "Url da imagem", "80", "Insira aqui a url da imagem que deseja que apareça no lugar do botão pagar agora.");
    defineGatewayField("moipapi", "text", "url_logo_boleto", "", "Url logo boleto", "22", "Digite a url da logo que deverá ser exibida no boleto ex: http:\\seusite.com\\logo.jpg");
    defineGatewayField("moipapi", "dropdown", "tp_venc", "", "Tipo de vencimento Dias", "22", "Corridos,Uteis");
    defineGatewayField("moipapi", "text", "prz_venc", "", "Prazo de vencimento Dias", "22", "Digite após quantos dias deverá o boleto vencer, conforme Tipo vencimento Dias");
    defineGatewayField("moipapi", "dropdown", "func_dbg", "", "Ativar debug", "22", "Não,Sim");
    defineGatewayField("moipapi", "yesno", "gravaDB", "on", "Retorno Automático", "", "Desmarque para não gravar as informações do retorno automático dos dados.");
    defineGatewayField("moipapi", "yesno", "capestado", "on", "Captura estado", "on", "Ativar/desativar captura de estado no cadastro do cliente, caso seja desativado será solicitado digitar o estado quando direcionado a Moip.");
    defineGatewayField("moipapi", "yesno", "habimage", "", "Habilita imagem", "", "Habilita trocar o botão pagar agora por uma imagem.");
    defineGatewayField("moipapi", "yesno", "habredir", "", "Habilita redirecionamento", "", "Habilita direcionar o cliente direto ao pagamento ao invráz dos detalhes da fatura ( Não precisará clicar no botão pagar.");
}
function moipapiformata_data($data)
{
    list($ano, $mes, $dia) = explode("-", $data);
    $data = (string) $dia . "-" . $mes . "-" . $ano;
    return $data;
}
function moipapiformata_estado($estado)
{
    $estado = strtoupper($estado);
    $acentos = array("A" => array("Á", "À", "Â", "Ã", "Ä", "á", "à", "â", "ã", "ä"), "E" => array("É", "È", "Ê", "Ë", "é", "è", "ê", "ë"), "I" => array("Í", "Ì", "Î", "Ï", "í", "ì", "î", "ï"), "O" => array("Ó", "Ò", "Ô", "Õ", "Ö", "ó", "ò", "ô", "õ", "ö"), "U" => array("Ú", "Ù", "Û", "Ü", "ú", "ù", "û", "ü"), "misc" => array(" ", ".", ",", ":", ";", "<", ">", "\\", "/", "\"", "'", "|", "?", "!", "@", "#", "\$", "%", "&", "*", "(", ")", "[", "]", "{", "}", "Ž", "`", "~", "^", "š", "-", "_", "=", "+", "1", "2", "3", "4", "5", "6", "7", "8", "9", "0"));
    $estado = str_ireplace($acentos["A"], "A", $estado);
    $estado = str_ireplace($acentos["E"], "E", $estado);
    $estado = str_ireplace($acentos["I"], "I", $estado);
    $estado = str_ireplace($acentos["O"], "O", $estado);
    $estado = str_ireplace($acentos["U"], "U", $estado);
    $estado = str_ireplace($acentos["misc"], "", $estado);
    switch ($estado) {
        case "ACRE":
            $estado = "AC";
            break;
        case "ALAGOAS":
            $estado = "AL";
            break;
        case "AMAPA":
            $estado = "AP";
            break;
        case "AMAZONAS":
            $estado = "AM";
            break;
        case "BAHIA":
            $estado = "BA";
            break;
        case "CEARA":
            $estado = "CE";
            break;
        case "DISTRITOFEDERAL":
            $estado = "DF";
            break;
        case "ESPIRITOSANTO":
            $estado = "ES";
            break;
        case "GOIAS":
            $estado = "GO";
            break;
        case "MARANHAO":
            $estado = "MA";
            break;
        case "MATOGROSSO":
            $estado = "MT";
            break;
        case "MATOGROSSODOSUL":
            $estado = "MS";
            break;
        case "MINASGERAIS":
            $estado = "MG";
            break;
        case "PARA":
            $estado = "PA";
            break;
        case "PARAIBA":
            $estado = "PB";
            break;
        case "PARANA":
            $estado = "PR";
            break;
        case "PERNAMBUCO":
            $estado = "PE";
            break;
        case "PIAUI":
            $estado = "PI";
            break;
        case "RIODEJANEIRO":
            $estado = "RJ";
            break;
        case "RIOGRANDEDONORTE":
            $estado = "RN";
            break;
        case "RIOGRANDEDOSUL":
            $estado = "RS";
            break;
        case "RONDONIA":
            $estado = "RO";
            break;
        case "RORAIMA":
            $estado = "RR";
            break;
        case "SANTACATARINA":
            $estado = "SC";
            break;
        case "SAOPAULO":
            $estado = "SP";
            break;
        case "SERGIPE":
            $estado = "SE";
            break;
        case "TOCANTINS":
            $estado = "TO";
            break;
    }
    return $estado;
}
function moipapi_link($params)
{
    $data_vencimento = moipapiformata_data($params["duedate"]);
    $estado = moipapiformata_estado($params["clientdetails"]["state"]);
    $taxa = $params["taxa_porc"] / 100 * $params["amount"] + $params["taxa_reais"];
    $valor_total = $params["amount"] + $taxa;
    $valor_total = number_format($valor_total, "2", ".", "");
    $valor = number_format($valor_total, "2", ".", "");
    $taxa = number_format($taxa, "2", ".", "");
    $cl_cpf = full_query("SELECT id FROM tblcustomfields WHERE fieldname='CPF'");
    $cl_cpf = mysql_fetch_array($cl_cpf);
    $cl_cpf = $cl_cpf["id"];
    $cl_cnpj = full_query("SELECT id FROM tblcustomfields WHERE fieldname='CNPJ'");
    $cl_cnpj = mysql_fetch_array($cl_cnpj);
    $cl_cnpj = $cl_cnpj["id"];
    $vl_cpf = $customfield[3];
    $key_plata = rand(0, 1000);
    $db_invoice_items = select_query("tblinvoiceitems", "", array("invoiceid" => (int) $params["invoiceid"], "userid" => (int) $params["clientdetails"]["userid"]));
    while ($dados = mysql_fetch_array($db_invoice_items)) {
        $db_id = $dados["id"];
        $invoice_items[(string) $db_id] = array("description" => $dados["description"], "amount" => $dados["amount"]);
    }
    unset($db_invoice_items);
    unset($dados);
    unset($db_id);
    if (0 < $taxa) {
        $code .= "<br />Para pagamentos efetuados com o Moip será cobrada uma taxa adicional de ";
        if (0 < $params["taxa_porc"]) {
            $code .= "<strong>" . $params["taxa_porc"] . "%</strong>";
            $code .= 0 < $params["taxa_reais"] ? " + " : "";
        }
        $code .= 0 < $params["taxa_reais"] ? "<strong>R\$ " . $params["taxa_reais"] . "</strong>" : "";
    }
    $descricao = $params["description"];
    $auth = "HMTE0EEQEIXZWZGTO5DHI5T4EIRTZFP9" . ":" . "GIC8GQ1F8HRYA9OBGWMDFNPTLRF8I1XZPQVKJ7LA";
    $logradouro = $params["clientdetails"]["address1"];
    $cidade = $params["clientdetails"]["city"];
    $bairro = $params["clientdetails"]["address2"];
    $telefone = str_replace(" ", "", str_replace("-", "", $params["clientdetails"]["phonenumber"]));
    $first_name = $params["clientdetails"]["firstname"];
    $nome_pagador = $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"];
    $cep = str_replace("-", "", $params["clientdetails"]["postcode"]);
    $email_pagador = $params["clientdetails"]["email"];
    $trans_id = $params["invoiceid"];
    $userid = $params["clientdetails"]["userid"];
    $numeros = explode(",", $logradouro);
    $numero = $numeros[1];
    $ct_moip = $params["emailpd"];
    $diasexp = "5";
    $btipo = $params["tp_venc"];
    $prz_venc = $params["prz_venc"];
    $instboleto1 = $params["inst_boleto1"];
    $endereco = $numeros[0];
    $companyname = $params["companyname"];
    $layout = $params["layout"];
    $inst_boleto1 = $params["inst_boleto1"];
    $inst_boleto2 = $params["inst_boleto2"];
    $inst_boleto3 = $params["inst_boleto3"];
    $url_notifica = $params["systemurl"] . "/modules/gateways/callback/moipapi.php";
    $url_retorno = $params["systemurl"] . "/viewinvoice.php?id=" . $params["invoiceid"];
    $xml = "<EnviarInstrucao>\n<InstrucaoUnica>\n<Razao>" . $companyname . " - Fatura: #" . $trans_id . " </Razao>\n<Valores><Valor moeda='BRL'>" . $valor_total . "</Valor></Valores>\n<IdProprio>Pedido: " . $trans_id . " Transação " . $userid . " " . $key_plata . "</IdProprio>\n<Pagador><Nome>" . $nome_pagador . "</Nome>\n<Email>" . $email_pagador . "</Email>\n<TelefoneCelular>" . $telefone . "</TelefoneCelular>\n<Apelido>" . $first_name . "</Apelido>\n<Identidade>111.111.111-11</Identidade>\n<EnderecoCobranca><Logradouro>" . $endereco . "</Logradouro>\n<Numero>" . $numero . "</Numero><Complemento></Complemento>\n<Bairro>" . $bairro . "</Bairro>\n<Cidade>" . $cidade . "</Cidade>\n<Estado>" . $estado . "</Estado>\n<Pais>BRA</Pais>\n<CEP>" . $cep . "</CEP>\n<TelefoneFixo>" . $telefone . "</TelefoneFixo>\n</EnderecoCobranca>\n</Pagador>\n<Mensagens>\n         <Mensagem>Pedido: " . $trans_id . " Transação " . $userid . " " . $key_plata . "</Mensagem>\n         <Mensagem>" . $companyname . "</Mensagem>\n      </Mensagens>\n<Recebedor>\n      <LoginMoIP>" . $ct_moip . "</LoginMoIP>\n      <Apelido>" . $companyname . "</Apelido>\n    </Recebedor>\n<URLRetorno>" . $url_retorno . "</URLRetorno>\n<URLNotificacao>" . $url_notifica . "</URLNotificacao>\n</InstrucaoUnica></EnviarInstrucao>\n";
    $xml2 = utf8_encode($xml);
    $header[] = "Authorization: Basic " . base64_encode($auth);
    $url = "https://www.moip.com.br/ws/alpha/EnviarInstrucao/Unica";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_USERPWD, $auth);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0");
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $xml2);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $ret = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    $resposta_moip = simplexml_load_string($ret);
    $token_resposta = $res->Resposta->Token;
    $ret_sucesso = explode("Sucesso", $ret);
    $token_new = $ret_sucesso[1];
    $a = explode("<Token>", $token_new);
    $b = explode("</Token>", $a[1]);
    $token_link = $b[0];
    $resp_a = explode("<Status>", $ret);
    $resp_b = explode("</Status>", $resp_a[1]);
    $status_token = $resp_b[0];
    $erro_cod_a = explode("<Erro Codigo=\"", $ret);
    $erro_cod_b = explode("\">", $erro_cod_a[1]);
    $erro_cod = $erro_cod_b[0];
    $erro_msg_a = explode("<Erro Codigo=\"" . $erro_cod . "\">", $ret);
    $erro_msg_b = explode("</Erro>", $erro_msg_a[1]);
    $erro_msg = $erro_msg_b[0];
    $erro_msgiso = $erro_msg;
    if ($params["func_dbg"] == Sim) {
        $code .= "erro = " . $err . ", " . $xmll . " = " . $xml2 . ", ret " . $ret . ", token= " . $token_link . " ";
    }
    if ($status_token == "Sucesso") {
    }
    if ($estado == "") {
        $erro_msg = "Cadastro do cliente com erro no campo estado";
        $erro_msgiso = "Cadastro do cliente com erro no campo estado";
    }
    $code .= "<form name=\"pagamentomoip\" action=\"https://www.moip.com.br/Instrucao.do?\" method=\"get\" accept-charset=\"ISO-8859-1\">\n<input name=\"token\" type=\"hidden\" value=\"" . $token_link . "\"> <input name=\"layout\" type=\"hidden\" value=\"" . $layout . "\">";
    if ($params["obs"] != "") {
        $code .= "Observações: <strong>" . $params["obs"] . "</strong>";
    }
    $code .= "<br />Total a pagar: <strong> R\$ " . $valor_total . " </strong><br />";
    if (0 < count($invoice_items)) {
        $loop = 1;
        foreach ($invoice_items as $key => $value) {
        }
    }
    if ($erro_msg != "") {
        $code .= " <font color=#FF0000> Erro: <strong> ' " . $erro_msgiso . "; ' Volte e corrija-o, caso necessário contate o suporte da cw2.</strong> </font>";
    } else {
        if ($params["habimage"] != "") {
            $code .= "<input type=\"image\" name=\"submit\" src=\"" . $params["url_image"] . "\" alt=\"Pagar\" border=\"0\">";
        } else {
            $code .= "\n <input type=\"submit\" value=\"" . $params["langpaynow"] . "\">";
        }
    }
    $code .= "\n</form>";
    $teste = $params["habredir"];
    if ($params["habredir"] == on) {
        header("Location: https://www.moip.com.br/Instrucao.do?token=" . $token_link);
    }
    return $code;
}

?>