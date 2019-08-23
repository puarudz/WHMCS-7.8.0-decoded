<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

class zipfile
{
    public $datasec = array();
    public $ctrl_dir = array();
    public $eof_ctrl_dir = "PK\5\6\0\0\0\0";
    public $old_offset = 0;
    public function add_dir($name)
    {
        $name = str_replace("\\", "/", $name);
        $fr = "PK\3\4";
        $fr .= "\n";
        $fr .= "";
        $fr .= "";
        $fr .= "";
        $fr .= pack("V", 0);
        $fr .= pack("V", 0);
        $fr .= pack("V", 0);
        $fr .= pack("v", strlen($name));
        $fr .= pack("v", 0);
        $fr .= $name;
        $fr .= pack("V", $crc);
        $fr .= pack("V", $c_len);
        $fr .= pack("V", $unc_len);
        $this->datasec[] = $fr;
        $new_offset = strlen(implode("", $this->datasec));
        $cdrec = "PK\1\2";
        $cdrec .= "";
        $cdrec .= "\n";
        $cdrec .= "";
        $cdrec .= "";
        $cdrec .= "";
        $cdrec .= pack("V", 0);
        $cdrec .= pack("V", 0);
        $cdrec .= pack("V", 0);
        $cdrec .= pack("v", strlen($name));
        $cdrec .= pack("v", 0);
        $cdrec .= pack("v", 0);
        $cdrec .= pack("v", 0);
        $cdrec .= pack("v", 0);
        $ext = "";
        $ext = "ÿÿÿÿ";
        $cdrec .= pack("V", 16);
        $cdrec .= pack("V", $this->old_offset);
        $this->old_offset = $new_offset;
        $cdrec .= $name;
        $this->ctrl_dir[] = $cdrec;
    }
    public function add_file($data, $name)
    {
        $name = str_replace("\\", "/", $name);
        $fr = "PK\3\4";
        $fr .= "\24";
        $fr .= "";
        $fr .= "\10";
        $fr .= "";
        $unc_len = strlen($data);
        $crc = crc32($data);
        $zdata = gzcompress($data);
        $zdata = substr(substr($zdata, 0, strlen($zdata) - 4), 2);
        $c_len = strlen($zdata);
        $fr .= pack("V", $crc);
        $fr .= pack("V", $c_len);
        $fr .= pack("V", $unc_len);
        $fr .= pack("v", strlen($name));
        $fr .= pack("v", 0);
        $fr .= $name;
        $fr .= $zdata;
        $fr .= pack("V", $crc);
        $fr .= pack("V", $c_len);
        $fr .= pack("V", $unc_len);
        $this->datasec[] = $fr;
        $new_offset = strlen(implode("", $this->datasec));
        $cdrec = "PK\1\2";
        $cdrec .= "";
        $cdrec .= "\24";
        $cdrec .= "";
        $cdrec .= "\10";
        $cdrec .= "";
        $cdrec .= pack("V", $crc);
        $cdrec .= pack("V", $c_len);
        $cdrec .= pack("V", $unc_len);
        $cdrec .= pack("v", strlen($name));
        $cdrec .= pack("v", 0);
        $cdrec .= pack("v", 0);
        $cdrec .= pack("v", 0);
        $cdrec .= pack("v", 0);
        $cdrec .= pack("V", 32);
        $cdrec .= pack("V", $this->old_offset);
        $this->old_offset = $new_offset;
        $cdrec .= $name;
        $this->ctrl_dir[] = $cdrec;
    }
    public function file()
    {
        $data = implode("", $this->datasec);
        $ctrldir = implode("", $this->ctrl_dir);
        return $data . $ctrldir . $this->eof_ctrl_dir . pack("v", sizeof($this->ctrl_dir)) . pack("v", sizeof($this->ctrl_dir)) . pack("V", strlen($ctrldir)) . pack("V", strlen($data)) . "";
    }
}
function get_structure($db)
{
    @ini_set("memory_limit", "512M");
    @ini_set("max_execution_time", 0);
    @set_time_limit(0);
    $tables = full_query("SHOW TABLES FROM `" . $db . "`;");
    while ($td = mysql_fetch_array($tables)) {
        $table = $td[0];
        if ($table != "modlivehelp_ip2country") {
            $r = full_query("SHOW CREATE TABLE `" . $table . "`");
            if ($r) {
                $insert_sql = "";
                $d = mysql_fetch_array($r);
                $d[1] .= ";";
                $sql[] = str_replace("\r\n", "", $d[1]);
                $table_query = full_query("SELECT * FROM `" . $table . "`");
                $num_fields = mysql_num_fields($table_query);
                while ($fetch_row = mysql_fetch_array($table_query)) {
                    $insert_sql .= "INSERT INTO " . $table . " VALUES(";
                    for ($n = 1; $n <= $num_fields; $n++) {
                        $m = $n - 1;
                        $insert_sql .= "'" . mysql_real_escape_string($fetch_row[$m]) . "', ";
                    }
                    $insert_sql = substr($insert_sql, 0, -2);
                    $insert_sql .= ");\r\n";
                }
                $sql[] = $insert_sql . "\r\n";
            }
        }
    }
    return implode("\r\n", $sql);
}
function generateBackup()
{
    global $db_name;
    set_time_limit(0);
    $zipfile = new zipfile();
    $zipfile->add_dir("/");
    $zipfile->add_file($structure = get_structure($db_name), (string) $db_name . ".sql");
    return $zipfile->file();
}

?>