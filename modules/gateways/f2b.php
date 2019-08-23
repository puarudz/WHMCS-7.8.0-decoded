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
$GATEWAYMODULE["f2bname"] = "f2b";
$GATEWAYMODULE["f2bvisiblename"] = "Cobrança - F2b";
$GATEWAYMODULE["f2btype"] = "Invoices";
function f2b_activate()
{
    defineGatewayField("f2b", "text", "conta", "", "Número da Conta", "16", "Preencher o número de sua conta F2b sem espaços (normalmente começa com 9023... e possui 16 dígitos)");
    defineGatewayField("f2b", "text", "senha", "", "Senha", "12", "Informar a senha, caso você tenha cadastrado uma senha somente para WebServices, utilize-a aqui.");
    defineGatewayField("f2b", "text", "taxa", "", "Taxa de Cobrança", "10", "(formato 0,00)");
    defineGatewayField("f2b", "text", "tipo_taxa", "", "Tipo de Taxa", "1", "0 = R\$ (Reais) e 1 = % (Porcentagem)");
    defineGatewayField("f2b", "text", "tipo_cobranca", "", "Meios de Pagto Aceitos", "10", "B - Boleto; C - Cartão de crédito; D - Cartão de débito; T - Transferência On-line. <br>Ex.: BCD (Aceitar Boleto, Crédito e Débito)<br>");
}
function f2b_link($params)
{
    $result = select_query("tblinvoices", "id,duedate", array("id" => $params["invoiceid"]));
    $data = mysql_fetch_array($result);
    $duedate = $data["duedate"];
    $sigla_uf = "";
    if ($params["clientdetails"]["state"] == "Acre") {
        $sigla_uf = "AC";
    } else {
        if ($params["clientdetails"]["state"] == "Alagoas") {
            $sigla_uf = "AL";
        } else {
            if ($params["clientdetails"]["state"] == "Amapa" || $params["clientdetails"]["state"] == "Amapá") {
                $sigla_uf = "AP";
            } else {
                if ($params["clientdetails"]["state"] == "Amazonas") {
                    $sigla_uf = "AM";
                } else {
                    if ($params["clientdetails"]["state"] == "Bahia") {
                        $sigla_uf = "BA";
                    } else {
                        if ($params["clientdetails"]["state"] == "Ceara" || $params["clientdetails"]["state"] == "Ceará") {
                            $sigla_uf = "CE";
                        } else {
                            if ($params["clientdetails"]["state"] == "Distrito Federal") {
                                $sigla_uf = "DF";
                            } else {
                                if ($params["clientdetails"]["state"] == "Espirito Santo") {
                                    $sigla_uf = "ES";
                                } else {
                                    if ($params["clientdetails"]["state"] == "Goias" || $params["clientdetails"]["state"] == "Goiás") {
                                        $sigla_uf = "GO";
                                    } else {
                                        if ($params["clientdetails"]["state"] == "Maranhao" || $params["clientdetails"]["state"] == "Maranhão") {
                                            $sigla_uf = "MA";
                                        } else {
                                            if ($params["clientdetails"]["state"] == "Mato Grosso") {
                                                $sigla_uf = "MT";
                                            } else {
                                                if ($params["clientdetails"]["state"] == "Mato Grosso do Sul") {
                                                    $sigla_uf = "MS";
                                                } else {
                                                    if ($params["clientdetails"]["state"] == "Minas Gerais") {
                                                        $sigla_uf = "MG";
                                                    } else {
                                                        if ($params["clientdetails"]["state"] == "Para" || $params["clientdetails"]["state"] == "Pará") {
                                                            $sigla_uf = "PA";
                                                        } else {
                                                            if ($params["clientdetails"]["state"] == "Paraiba" || $params["clientdetails"]["state"] == "Paraíba") {
                                                                $sigla_uf = "PB";
                                                            } else {
                                                                if ($params["clientdetails"]["state"] == "Parana" || $params["clientdetails"]["state"] == "Paraná") {
                                                                    $sigla_uf = "PR";
                                                                } else {
                                                                    if ($params["clientdetails"]["state"] == "Pernambuco") {
                                                                        $sigla_uf = "PE";
                                                                    } else {
                                                                        if ($params["clientdetails"]["state"] == "Piaui" || $params["clientdetails"]["state"] == "Piauí") {
                                                                            $sigla_uf = "PI";
                                                                        } else {
                                                                            if ($params["clientdetails"]["state"] == "Rio de Janeiro") {
                                                                                $sigla_uf = "RJ";
                                                                            } else {
                                                                                if ($params["clientdetails"]["state"] == "Rio Grande do Norte") {
                                                                                    $sigla_uf = "RN";
                                                                                } else {
                                                                                    if ($params["clientdetails"]["state"] == "Rio Grande do Sul") {
                                                                                        $sigla_uf = "RS";
                                                                                    } else {
                                                                                        if ($params["clientdetails"]["state"] == "Rondonia" || $params["clientdetails"]["state"] == "Rondônia") {
                                                                                            $sigla_uf = "RO";
                                                                                        } else {
                                                                                            if ($params["clientdetails"]["state"] == "Roraima") {
                                                                                                $sigla_uf = "RR";
                                                                                            } else {
                                                                                                if ($params["clientdetails"]["state"] == "Santa Catarina") {
                                                                                                    $sigla_uf = "SC";
                                                                                                } else {
                                                                                                    if ($params["clientdetails"]["state"] == "Sao Paulo" || $params["clientdetails"]["state"] == "São Paulo") {
                                                                                                        $sigla_uf = "SP";
                                                                                                    } else {
                                                                                                        if ($params["clientdetails"]["state"] == "Sergipe") {
                                                                                                            $sigla_uf = "SE";
                                                                                                        } else {
                                                                                                            if ($params["clientdetails"]["state"] == "Tocantins") {
                                                                                                                $sigla_uf = "TO";
                                                                                                            } else {
                                                                                                                $sigla_uf = $params["clientdetails"]["state"];
                                                                                                            }
                                                                                                        }
                                                                                                    }
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    $code = "\n<form action=\"http://www.f2b.com.br/BillingWeb\" method=\"post\" target=\"_BLANK\">\n\n<input type=\"hidden\" name=\"conta\" value=\"" . $params["conta"] . "\">\n<input type=\"hidden\" name=\"senha\" value=\"" . $params["senha"] . "\">\n\n<input type=\"hidden\" name=\"valor\" value=\"" . $params["amount"] . "\">\n<input type=\"hidden\" name=\"taxa\" value=\"" . $params["taxa"] . "\">\n<input type=\"hidden\" name=\"tipo_taxa\" value=\"" . $params["tipo_taxa"] . "\">\n<input type=\"hidden\" name=\"tipo_cobranca\" value=\"" . $params["tipo_cobranca"] . "\">\n\n<input type=\"hidden\" name=\"demonstrativo_1\" value=\"" . $params["description"] . "\">\n<input type=\"hidden\" name=\"demonstrativo_2\" value=\"" . $params["clientdetails"]["customfields0"] . "\">\n<input type=\"hidden\" name=\"demonstrativo_3\" value=\"" . $params["clientdetails"]["customfields1"] . "\">\n<input type=\"hidden\" name=\"demonstrativo_4\" value=\"" . $params["clientdetails"]["customfields2"] . "\">\n<input type=\"hidden\" name=\"demonstrativo_5\" value=\"" . $params["clientdetails"]["customfields3"] . "\">\n<input type=\"hidden\" name=\"demonstrativo_6\" value=\"" . $params["clientdetails"]["customfields4"] . "\">\n<input type=\"hidden\" name=\"demonstrativo_7\" value=\"" . $params["clientdetails"]["customfields5"] . "\">\n<input type=\"hidden\" name=\"demonstrativo_8\" value=\"" . $params["clientdetails"]["customfields6"] . "\">\n<input type=\"hidden\" name=\"demonstrativo_9\" value=\"" . $params["clientdetails"]["customfields7"] . "\">\n<input type=\"hidden\" name=\"demonstrativo_10\" value=\"" . $params["clientdetails"]["customfields8"] . "\">\n\n<input type=\"hidden\" name=\"vencimento\" value=\"" . $duedate . "\">\n\n<input type=\"hidden\" name=\"nome\" value=\"" . $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"] . "\">\n<input type=\"hidden\" name=\"email_1\" value=\"" . $params["clientdetails"]["email"] . "\">\n\n<input type=\"hidden\" name=\"endereco_logradouro\" value=\"" . $params["clientdetails"]["address1"] . "\">\n<input type=\"hidden\" name=\"endereco_numero\" value=\"0\">\n<input type=\"hidden\" name=\"endereco_bairro\" value=\"" . $params["clientdetails"]["address2"] . "\">\n<input type=\"hidden\" name=\"endereco_cidade\" value=\"" . $params["clientdetails"]["city"] . "\">\n<input type=\"hidden\" name=\"endereco_estado\" value=\"" . $sigla_uf . "\">\n<input type=\"hidden\" name=\"endereco_cep\" value=\"" . $params["clientdetails"]["postcode"] . "\">";
    $code .= "<input type=\"submit\" value=\"" . $params["langpaynow"] . "\"></form>";
    return $code;
}

?>