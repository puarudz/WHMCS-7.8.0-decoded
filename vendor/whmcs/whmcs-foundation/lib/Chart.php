<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class Chart
{
    public $chartcount = 0;
    public function __construct()
    {
    }
    public function drawChart($type, $data, $args = array(), $height = "300px", $width = "100%")
    {
        global $aInt;
        $datafunc = !is_array($data) ? $data : "";
        if ($datafunc && !function_exists("json_encode")) {
            return "JSON appears to be missing from your PHP build and is required for graphs to function. Please recompile PHP with JSON included and then try again.";
        }
        if ($datafunc && isset($_POST["chartdata"]) && $_POST["chartdata"] == $datafunc) {
            if (function_exists("chartdata_" . $datafunc)) {
                $chartdata = call_user_func("chartdata_" . $datafunc);
                foreach ($chartdata["cols"] as $k => $col) {
                    if (isset($chartdata["cols"][$k]["label"])) {
                        $chartdata["cols"][$k]["label"] = strval($chartdata["cols"][$k]["label"]);
                    }
                }
                echo json_encode($chartdata);
                exit;
            } else {
                exit("Function Not Found");
            }
        } else {
            if ($this->chartcount == 0) {
                if (is_string($aInt->headOutput)) {
                    $aInt->headOutput .= "<script type=\"text/javascript\" src=\"https://www.google.com/jsapi\"></script>";
                } else {
                    if (is_array($aInt->headOutput)) {
                        $aInt->addHeadOutput("<script type=\"text/javascript\" src=\"https://www.google.com/jsapi\"></script>");
                    }
                }
            }
            $this->chartcount++;
            $options = array();
            if (!isset($args["legendpos"])) {
                $args["legendpos"] = "top";
            }
            $options[] = "legend: {position: \"" . $args["legendpos"] . "\"}";
            if (isset($args["title"])) {
                $options[] = "title: '" . $args["title"] . "'";
            }
            if (isset($args["xlabel"])) {
                $options[] = "hAxis: {title: \"" . $args["xlabel"] . "\"}";
            }
            $vaxis = array();
            if (isset($args["ylabel"])) {
                $vaxis[] = "title: \"" . $args["ylabel"] . "\"";
            }
            if (isset($args["minyvalue"])) {
                $vaxis[] = "minValue: \"" . $args["minyvalue"] . "\"";
            }
            if (isset($args["maxyvalue"])) {
                $vaxis[] = "maxValue: \"" . $args["maxyvalue"] . "\"";
            }
            if (isset($args["gridlinescount"])) {
                $vaxis[] = "gridlines: {count:" . $args["gridlinescount"] . "}";
            }
            if (isset($args["minorgridlinescount"])) {
                $vaxis[] = "minorGridlines: {color:\"#efefef\",count:" . $args["minorgridlinescount"] . "}";
            }
            if (count($vaxis)) {
                $options[] = "vAxis: {" . implode(",", $vaxis) . "}";
            }
            if ($args["colors"]) {
                $colors = $args["colors"];
                $colors = explode(",", $colors);
                foreach ($colors as $i => $color) {
                    $colors[$i] = "\"" . $color . "\"";
                }
                $options[] = "colors: [" . implode(",", $colors) . "]";
            }
            if ($args["chartarea"]) {
                $chartarea = explode(",", $args["chartarea"]);
                $options[] = "chartArea: {left:" . $chartarea[0] . ",top:" . $chartarea[1] . ",width:\"" . $chartarea[2] . "\",height:\"" . $chartarea[3] . "\"}";
            }
            if (isset($args["stacked"]) && $args["stacked"]) {
                $options[] = "isStacked: true";
            }
            $chartUniqueId = time() . $this->chartcount;
            $chartName = "drawChart" . $chartUniqueId;
            $output = "\n            <script type=\"text/javascript\">\n            google.load(\"visualization\", \"1\", {packages:[\"" . ($type == "Geo" ? "geochart" : "corechart") . "\"]});\n            google.setOnLoadCallback(" . $chartName . ");\n            function " . $chartName . "() {";
            if ($datafunc) {
                $output .= "\n            var jsonData = \$.ajax({\n                url: \"" . $_SERVER["PHP_SELF"] . "\",\n                type: \"POST\",\n                data: \"chartdata=" . $datafunc . "\",\n                dataType:\"json\",\n                async: false\n            }).responseText;\n            ";
            } else {
                foreach ($data["cols"] as $k => $col) {
                    if (isset($data["cols"][$k]["label"])) {
                        $data["cols"][$k]["label"] = strval($data["cols"][$k]["label"]);
                    }
                }
                foreach ($data["rows"] as $k => $row) {
                    if (isset($data["rows"][$k]["c"])) {
                        $data["rows"][$k]["c"][0]["v"] = strval($data["rows"][$k]["c"][0]["v"]);
                        $data["rows"][$k]["c"][1]["v"] = floatval($data["rows"][$k]["c"][1]["v"]);
                        if (!empty($data["rows"][$k]["c"][1]["f"])) {
                            $data["rows"][$k]["c"][1]["f"] = strval($data["rows"][$k]["c"][1]["f"]);
                        } else {
                            unset($data["rows"][$k]["c"][1]["f"]);
                        }
                    }
                }
                $sanitizedData = json_encode($data, JSON_HEX_APOS);
                $output .= "\n                var jsonData = '" . $sanitizedData . "';\n            ";
            }
            $output .= "\n        var data = new google.visualization.DataTable(jsonData);\n        var options = { " . implode(",", $options) . " };\n        var chart = new google.visualization." . $type . "Chart(document.getElementById(\"chartcont" . $chartUniqueId . "\"));\n        chart.draw(data,options);\n        }\n        </script>\n        <div id=\"chartcont" . $chartUniqueId . "\" style=\"width:" . $width . ";height:" . $height . ";\"><div style=\"padding-top:" . round($height / 2 - 10, 0) . "px;text-align:center;\"><img src=\"images/loading.gif\" /> Loading...</div></div>\n        ";
            $aInt->chartFunctions[] = $chartName;
            return $output;
        }
    }
}

?>