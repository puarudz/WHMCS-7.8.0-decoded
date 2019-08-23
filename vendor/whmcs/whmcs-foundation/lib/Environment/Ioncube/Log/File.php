<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Environment\Ioncube\Log;

class File extends \WHMCS\Model\AbstractModel implements \WHMCS\Environment\Ioncube\Contracts\InspectedFileInterface
{
    protected $table = "tblioncube_file_log";
    public $timestamps = false;
    protected $casts = array("bundled_php_versions" => "array", "loaded_in_php" => "array");
    protected $fillable = array("filename", "content_hash", "encoder_version", "bundled_php_versions", "loaded_in_php", "target_php_version");
    private $analyzer = NULL;
    public function createTable($drop = false)
    {
        $schemaBuilder = \WHMCS\Database\Capsule::schema();
        if ($drop) {
            $schemaBuilder->dropIfExists($this->getTable());
        }
        if (!$schemaBuilder->hasTable($this->getTable())) {
            $schemaBuilder->create($this->getTable(), function ($table) {
                $table->increments("id");
                $table->text("filename")->default("");
                $table->string("content_hash", 512)->default("");
                $table->string("encoder_version", 16)->default("");
                $table->string("bundled_php_versions", 128)->default("");
                $table->string("loaded_in_php", 128)->default("");
                $table->string("target_php_version", 16)->default("");
            });
        }
    }
    public function getFileFingerprint()
    {
        return $this->filename . "::" . $this->content_hash;
    }
    public function replaceAll(array $models)
    {
        static::query()->delete();
        $values = array();
        foreach ($models as $item) {
            if ($item instanceof \WHMCS\Model\AbstractModel) {
                $data = $item->getAttributes();
                if (array_key_exists("id", $data)) {
                    unset($data["id"]);
                }
                $values[] = $data;
            }
        }
        $this->insert($values);
        return $this;
    }
    protected function factoryAnalyzer()
    {
        $analyzer = new \WHMCS\Environment\Ioncube\EncodedFile($this->filename, $this->content_hash, $this->encoder_version, $this->bundled_php_versions, $this->target_php_version);
        return $analyzer;
    }
    public function getAnalyzer()
    {
        if (!$this->analyzer) {
            $this->setAnalyzer($this->factoryAnalyzer());
        }
        return $this->analyzer;
    }
    public function setAnalyzer($analyzer)
    {
        $this->analyzer = $analyzer;
        return $this;
    }
    public function getFilename()
    {
        return $this->filename;
    }
    public function getFileContentHash()
    {
        return $this->content_hash;
    }
    public function getEncoderVersion()
    {
        return $this->encoder_version;
    }
    public function getTargetPhpVersion()
    {
        return $this->target_php_version;
    }
    public function versionCompatibilityAssessment($phpVersion, \WHMCS\Environment\Ioncube\Contracts\LoaderInterface $loader = NULL)
    {
        if (!$loader) {
            $loader = new \WHMCS\Environment\Ioncube\Loader\LocalLoader();
        }
        $assessment = $loader->compatAssessment($phpVersion, $this);
        return $assessment;
    }
    public function getBundledPhpVersions()
    {
        return $this->bundled_php_versions ? $this->bundled_php_versions : array();
    }
    public function getLoadedInPhp()
    {
        return $this->loaded_in_php ? $this->loaded_in_php : array();
    }
    public function canRunOnPhpVersion($phpVersion)
    {
        return $this->getAnalyzer()->canRunOnPhpVersion($phpVersion);
    }
}

?>