<?php
/**
 * Created by PhpStorm.
 * User: mikus
 * Date: 2017.03.06.
 * Time: 13:54
 */

namespace AppBundle\Util\Topo;


class TopoFileCache
{
    private $cacheRoot;
    private $currentSubDir;

    protected  $routeKeyTable;

    public function  __construct(array $params)
    {
        $dir = $params['cache_dir'] . '/roads';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $this->cacheRoot = $dir;
        $this->routeKeyTable = array();
    }

    public function getCacheRoot()
    {
        return $this->cacheRoot;
    }

    public function getRouteOfEdid($edid)
    {
        if (array_key_exists(strtoupper($edid), $this->routeKeyTable) && isset($this->routeKeyTable[strtoupper($edid)])) {
            return $this->routeKeyTable[strtoupper($edid)];
        }
        return null;
    }

    public function getCacheSubDirectory($time)
    {
        $dir = $this->cacheRoot . '/' . date('Y-m-d', $time);
        if (!is_dir($dir)) {
            $this->setCurrentSubDir(null);
            return null;
        }
        $this->setCurrentSubDir($dir);
        return $dir;
    }

    private function setCurrentSubDir($subDir)
    {
        $this->currentSubDir = $subDir;
    }

    public function getCurrentSubDir()
    {
        return $this->currentSubDir;
    }

    protected function createCacheSubDirectory($time)
    {
        $dirName = $this->cacheRoot . '/' . date('Y-m-d', $time);
        mkdir($dirName, 0777, true);
        $this->setCurrentSubDir($dirName);
        return $dirName;
    }

    public function prepareFileCache($time)
    {
        $dir = $this->getCacheSubDirectory($time);
        if (!$dir) {
            $this->createCacheSubDirectory($time);
        }
    }

    public function initData(array $roads)
    {
        $dataToSend = array();
        foreach ($roads as $road) {
            $dataToSend[] = strtoupper($road["name"] . $road["direction"]["sign"]);
            $this->routeKeyTable[strtoupper($road["name"] . $road["direction"]["sign"])] = $road['route_number'];
        }
        return $dataToSend;
    }

    public function loadDataIntoArray(array &$responseArray, array &$edids)
    {
        $lastRouteNumber = '';
        foreach ($edids as $edid) {
            $routeNumber = substr($edid, 0, strpos(strtoupper($edid), 'U'));
            $file = $this->getCacheFile($routeNumber);
            if ($file) {
                // Mivel rendezett EDID lista jön be, ugyanazon útat fájljait ne töltsük be egymás után
                if ($lastRouteNumber != $routeNumber) {
                    $geometryData = json_decode(file_get_contents($file), true);
                    $lastRouteNumber = $routeNumber;
                }
                // Ez a vizsgálat azért kell, mert lehet üres a geometria adat, vagy nincs is
                if (isset($geometryData[$edid]) && count($geometryData[$edid])) {
                    unset($edids[array_search($edid, $edids)]);
                    $responseArray['edids'][] = array('edid' => $edid, 'geometry' => $geometryData[$edid]);
                }
            }
        }
    }

    public function getCacheFile($routeNumber)
    {
        $file = $this->currentSubDir . '/' . $routeNumber.'.json';
        if (!file_exists($file)) {
            return false;
        }
        return $file;
    }

    public function writeCacheFile($data, $file)
    {
        file_put_contents($file, json_encode($data));
    }
}