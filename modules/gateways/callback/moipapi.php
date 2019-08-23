<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require "../../../init.php";
$whmcs->load_function("gateway");
$whmcs->load_function("invoice");
$GATEWAY = getGatewayVariables("moipapi");
$gatewayname = $GATEWAY["paymentmethod"];
$id_transacao = $_POST["id_transacao"];
$transid = $_POST["cod_moip"];
$valor = $_POST["valor"];
$real = substr($valor, 0, -2);
$cent = substr($valor, -2);
$amount = $real . "." . $cent;
$parcelas = $_POST["parcelas"];
$status_pagamento = $_POST["status_pagamento"];
$email_consumidor = $_POST["email_consumidor"];
$fee = $_POST["tipo_pagamento"];
$data_hora = date("d/m/Y H:i:s");
$hora = date("H:i:s");
$transacao = explode(":", $_POST["id_transacao"]);
$transacao_novo = $transacao[1];
$tmp = explode("-", $transacao_novo);
$invoiceid = $params["invoiceid"];
$faturaid = str_replace(" ", "", $tmp[0]);
$varuser1 = explode(" ", $transacao_novo);
$userid = $varuser1[3];
echo $userid;
if ($tipo_pagamento == "BoletoBancario") {
    $tp_pagamento = "Boleto Bancário";
    $vr_taxa = 1.39;
    $percentual = 2.9 / 100;
} else {
    if ($tipo_pagamento == "DebitoBancario") {
        $tp_pagamento = "Débito Bancário";
        $vr_taxa = 0.39;
        $percentual = 2.9 / 100;
    } else {
        if ($tipo_pagamento == "FinanciamentoBancario") {
            $tp_pagamento = "Financiamento Bancário";
            $vr_taxa = 0.39;
            $percentual = 2.9 / 100;
        } else {
            if ($tipo_pagamento == "CartaoDeCredito") {
                $tp_pagamento = "CartÃ£o de Crédito";
                $vr_taxa = 0.39;
                $percentual = 7.4 / 100;
            } else {
                if ($tipo_pagamento == "CartaoDeDebito") {
                    $tp_pagamento = "CartÃ£o de Débito";
                    $vr_taxa = 0.39;
                    $percentual = 7.4 / 100;
                } else {
                    if ($tipo_pagamento == "CarteiraMoIP") {
                        $tp_pagamento = "Carteira Moip";
                        $vr_taxa = 0.39;
                        $percentual = 2.9 / 100;
                    } else {
                        if ($tipo_pagamento == "NaoDefinida") {
                            $tp_pagamento = "NÃ£o definida";
                        } else {
                            if ($tipo_pagamento == "") {
                                $tp_pagamento = "Indefinida";
                            }
                        }
                    }
                }
            }
        }
    }
}
$valor = $amount;
$valor_final = $valor - $percentual * $valor;
$amount_out = $valor_final - $vr_taxa;
$variacao = $valor - $amount_out;
if ($status_pagamento == "1") {
    addInvoicePayment($faturaid, (string) $transid . " Pagamento autorizado " . $tp_pagamento . " ás " . $hora . " hs Fatura:", $amount, $variacao, "   moipapi");
    logTransaction($gatewayname, $_POST, "Successful");
    echo "Sucesso1";
} else {
    if ($status_pagamento == "2") {
        $msg = "Pagamento Iniciado/abandonado: via " . $tp_pagamento . " ás " . $hora . " hs Fatura: ";
        addTransaction($userid, $faturaid, $msg, "000", "0.00", "0000", "moipapi", $transid, $faturaid);
        logTransaction($gatewayname, $_POST, "Successful");
        echo "Sucesso2";
    } else {
        if ($status_pagamento == "3") {
            $transid = "</a> Trans ID " . $transid . " Boleto Impresso ás " . $hora . " (<a href='https://www.moip.com.br/Boleto.do?id=" . $transid . "' title='2ª Via boleto'>'2ªVia boleto clique aqui' </a>)";
            $msg = "Boleto Impresso: via " . $tp_pagamento . " ás " . $hora . " hs Fatura:";
            addTransaction($userid, $faturaid, $msg, "0.00", "0.00", "0.00", "moipapi", $transid, $faturaid);
            logTransaction($gatewayname, $_POST, "Successful");
            echo "Sucesso3";
        } else {
            if ($status_pagamento == "4") {
                $msg = "Pagamento concluÃ­do: via " . $tp_pagamento . " ás " . $hora . " hs Fatura: ";
                addTransaction($userid, $faturaid, $msg, "000", "0.00", "0000", "moipapi", $transid, $faturaid);
                logTransaction($gatewayname, $_POST, "Successful");
                echo "Sucesso4";
            } else {
                if ($status_pagamento == "5") {
                    $msg = "Pagamento CANCELADO: via " . $tp_pagamento . " ás " . $hora . " hs Fatura: ";
                    addTransaction($userid, $faturaid, $msg, "000", "0.00", "0000", "moipapi", $transid, $faturaid);
                    logTransaction($gatewayname, $_POST, "Successful");
                    echo "Sucesso5";
                } else {
                    if ($status_pagamento == "6") {
                        $msg = "Pagamento em análise: via " . $tp_pagamento . " em " . $parcelas . " parcelas ás " . $hora . " hs Fatura: ";
                        addTransaction($userid, $faturaid, $msg, "000", "0.00", "0000", "moipapi", $transid, $faturaid);
                        logTransaction($gatewayname, $_POST, "Successful");
                        echo "Sucesso6";
                    } else {
                        if ($status_pagamento == "7") {
                            $msg = "Pagamento extornado: via " . $tp_pagamento . " em " . $parcelas . " parcelas ás " . $hora . " hs Fatura:";
                            addTransaction($userid, $faturaid, $msg, "000", "0.00", "0000", "moipapi", $transid, $faturaid);
                            logTransaction($gatewayname, $_POST, "Successful");
                            echo "Sucesso7";
                        } else {
                            $msg = "Retorno desconhecido: via " . $tp_pagamento . " ás " . $hora . " hs Fatura:";
                            addTransaction($userid, $faturaid, $msg, "00", "0.00", "0", "moipapi", $transid, $faturaid);
                            logTransaction($gatewayname, $_POST, "Successful");
                            echo "SucessoN";
                        }
                    }
                }
            }
        }
    }
}

?>