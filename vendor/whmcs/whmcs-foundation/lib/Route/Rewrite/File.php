<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Route\Rewrite;

class File extends \SplFileObject
{
    protected $beforeRuleSet = array();
    protected $ourRuleSet = array();
    protected $afterRuleSet = array();
    protected $ruleGenerator = NULL;
    protected $exclusivelyWhmcs = false;
    const FILE_DEFAULT = ".htaccess";
    const MARKER_BEGIN = "### BEGIN - WHMCS managed rules - DO NOT EDIT BETWEEN WHMCS MARKERS ###";
    const MARKER_END = "### END - WHMCS managed rules - DO NOT EDIT BETWEEN WHMCS MARKERS ###";
    public static function factory($filename, RuleSet $ruleGenerator = NULL)
    {
        if (substr($filename, 0, 1) != "/") {
            $filename = ROOTDIR . "/" . $filename;
        }
        $fileInfo = new \SplFileInfo($filename);
        if ($fileInfo->isDir() || $fileInfo->isLink()) {
            throw new \RuntimeException($filename . " is not a valid file.");
        }
        if ($fileInfo->isFile()) {
            if (!$fileInfo->isReadable()) {
                throw new \RuntimeException("Cannot read file " . $filename);
            }
            if (!$fileInfo->isWritable()) {
                throw new \RuntimeException("Cannot write file " . $filename);
            }
        }
        clearstatcache();
        $file = new static($filename, "c+");
        $operation = \WHMCS\Environment\OperatingSystem::isWindows() ? LOCK_EX : LOCK_EX | LOCK_NB;
        if (!$file->flock($operation)) {
            throw new \RuntimeException("Could not secure an advisory lock for file " . $filename);
        }
        $file->setFlags(\SplFileObject::DROP_NEW_LINE);
        $file->parse();
        if ($ruleGenerator) {
            $file->setRuleGenerator($ruleGenerator);
        }
        $file->inspectForExclusiveWhmcsRules();
        return $file;
    }
    public function parse()
    {
        $before = $ours = $after = array();
        $inOurs = false;
        $this->rewind();
        $i = 0;
        while (!$this->eof()) {
            if (10000 < $i) {
                break;
            }
            $i++;
            $rule = $this->current();
            if (trim($rule) == static::MARKER_BEGIN) {
                $inOurs = true;
                $this->next();
                continue;
            }
            if (trim($rule) == static::MARKER_END) {
                $inOurs = false;
                $this->next();
                continue;
            }
            if ($inOurs) {
                if ($rule = trim($rule)) {
                    $ours[] = $rule;
                }
            } else {
                if (count($ours)) {
                    $after[] = $rule;
                } else {
                    $before[] = $rule;
                }
            }
            $this->next();
        }
        $this->rewind();
        foreach ($after as $key => $rule) {
            if ($rule) {
                break;
            }
            unset($after[$key]);
        }
        foreach (array_reverse($before, true) as $key => $rule) {
            if ($rule) {
                break;
            }
            unset($before[$key]);
        }
        $this->setBeforeRuleSet($before)->setOurRuleSet($ours)->setAfterRuleSet($after);
    }
    public function isEmpty()
    {
        return !$this->getOurRuleSet() && !$this->getBeforeRuleSet() && !$this->getAfterRuleSet();
    }
    public function inspectForExclusiveWhmcsRules()
    {
        $ruleSet = $this->getRuleGenerator();
        $ours = $this->getOurRuleSet();
        $before = $this->getBeforeRuleSet();
        $after = $this->getAfterRuleSet();
        if (!$ours) {
            $before = $this->getRuleGenerator()->reduce($before);
            if ($before == $ruleSet->getLegacyRules() && !$after) {
                $this->setOurRuleSet($before)->setBeforeRuleSet(array());
                $this->setExclusivelyWhmcs(true);
            } else {
                $this->setExclusivelyWhmcs(false);
            }
        } else {
            if (empty($before) && empty($after)) {
                $this->setExclusivelyWhmcs(true);
            } else {
                $this->setExclusivelyWhmcs(false);
            }
        }
        return $this;
    }
    public function isInSync()
    {
        $ruleGenerator = $this->getRuleGenerator();
        $ours = $ruleGenerator->reduce($this->getOurRuleSet());
        $newRules = $ruleGenerator->reduce($ruleGenerator->generateRuleSet());
        return $ours == $newRules;
    }
    public function updateWhmcsRuleSet()
    {
        if ($this->isInSync()) {
            logActivity("Updated WHMCS Rewrite Rules: already up to date.");
            return $this;
        }
        $newRules = $this->getRuleGenerator()->generateRuleSet();
        $content = array_merge($this->getBeforeRuleSet(), array("", static::MARKER_BEGIN), $newRules, array(static::MARKER_END, ""), $this->getAfterRuleSet());
        $this->backupCurrentRules();
        $this->rewind();
        $this->fwrite(implode("\n", $content));
        $this->fflush();
        $this->ftruncate($this->ftell());
        logActivity("Updated WHMCS Rewrite Rules: new rules applied.");
        return $this;
    }
    public function backupCurrentRules()
    {
        $storedRules = \WHMCS\TransientData::getInstance()->retrieve("RewriteBackups");
        if ($storedRules) {
            $storedRules = json_decode($storedRules, true);
            if (!is_array($storedRules)) {
                $storedRules = array();
            }
        } else {
            $storedRules = array();
        }
        if (10 <= count($storedRules)) {
            array_pop($storedRules);
        }
        array_unshift($storedRules, array("before" => $this->getBeforeRuleSet(), "whmcs" => $this->getOurRuleSet(), "after" => $this->getAfterRuleSet()));
        \WHMCS\TransientData::getInstance()->store("RewriteBackup", json_encode($storedRules), 60 * 60 * 24 * 356);
        return $this;
    }
    public function isExclusivelyWhmcs()
    {
        return $this->exclusivelyWhmcs;
    }
    public function setExclusivelyWhmcs($exclusivelyWhmcs)
    {
        $this->exclusivelyWhmcs = $exclusivelyWhmcs;
        return $this;
    }
    public function getRuleGenerator()
    {
        if (!$this->ruleGenerator) {
            $this->setRuleGenerator(new RuleSet());
        }
        return $this->ruleGenerator;
    }
    public function setRuleGenerator($ruleGenerator)
    {
        $this->ruleGenerator = $ruleGenerator;
        return $this;
    }
    public function getBeforeRuleSet()
    {
        return $this->beforeRuleSet;
    }
    public function setBeforeRuleSet($beforeRuleSet)
    {
        $this->beforeRuleSet = $beforeRuleSet;
        return $this;
    }
    public function getOurRuleSet()
    {
        return $this->ourRuleSet;
    }
    public function setOurRuleSet($ourRuleSet)
    {
        $this->ourRuleSet = $ourRuleSet;
        return $this;
    }
    public function getAfterRuleSet()
    {
        return $this->afterRuleSet;
    }
    public function setAfterRuleSet($afterRuleSet)
    {
        $this->afterRuleSet = $afterRuleSet;
        return $this;
    }
}

?>