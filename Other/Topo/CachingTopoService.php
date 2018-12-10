<?php

namespace AppBundle\Util\Topo;

use AppBundle\Util\HttpClient\HttpClient;
use AppBundle\Util\Memcached;

class CachingTopoService extends TopoService
{
    private $cache;
    private $cacheDir;
    private $fileCache;
    private $useCache = true;

    public function setUseCache($useCache)
    {
        $this->useCache = $useCache;
        return $this;
    }

    public function __construct(HttpClient $httpClient, array $params, $logger, Memcached $cache)
    {
        parent::__construct($httpClient, $params, $logger);
        $this->cache = $cache;
        $this->cacheDir = $params['cache_dir'];
        $this->fileCache = new TopoFileCache($params);
    }

    /**
     *
     * @param array|string $edids
     * @param int $time
     * @return array
     * @throws TopoException
     */
    public function edidLookup($edids, $time)
    {
        if (!empty($edids)) {
            if (is_string($edids)) {
                $edids = array($edids);
            }

            $cached = array('edids' => array());
            if ($this->useCache) {
                $this->loadFromCache($cached, $edids, $time);
            }

            if (empty($edids)) { //our cache is up to date
                return $cached;
            }

            $newData = parent::edidLookup($edids, $time);

            if ($this->useCache) {
                $this->storeInMemCache($newData, $time);
            }

            $newData['edids'] = array_merge($newData['edids'], $cached['edids']);

            return $newData;

        }
        return array();
    }

    /**
     * @param array $resp
     * @param array $edids
     * @param int $time
     */
    protected function loadFromCache(array &$resp, array &$edids, $time)
    {
        $this->loadFromMemCache($resp, $edids, $time);
        $this->loadFromFileCache($resp, $edids, $time);
    }

    /**
     *
     * @param array $resp
     * @param array $edids
     * @param int $time
     */
    protected function loadFromMemCache(array &$resp, array &$edids, $time)
    {
        if (empty($edids)) {
            return;
        }

        $prefix = $this->getCacheKeyPrefix($time);
        $keys = array();
        foreach ($edids as $edid) {
            $keys[] = $prefix.$edid;
        }

        $data = $this->cache->getMulti($keys);

        foreach ($data as $key => $data) {
            $edid=substr($key, strrpos($key, '_')+1);
            $resp['edids'][] = array('edid' => $edid, 'geometry' => $data);
            unset($edids[array_search($edid, $edids)]);
        }
    }

    /**
     * @param int $time
     * @return string
     */
    protected function getCacheKeyPrefix($time)
    {
        return date('Y_m_d', $time).'_';
    }

    /**
     * @param array $resp
     * @param array $edids
     * @param int $time
     */
    protected function loadFromFileCache(array &$resp, array &$edids, $time)
    {
        if (!empty($edids) && $this->fileCache->getCacheSubDirectory($time)) {
            $this->fileCache->loadDataIntoArray($resp, $edids);
        }
    }

    /**
     *
     * @param array $items
     */
    protected function setEdidCache($items)
    {
        $this->cache->setMulti($items, 60*60*24 + rand(0, 3600));
    }

    protected function storeInMemCache(array $edids, $time)
    {
        if (!empty($edids)) {
            $toStore = array();
            $prefix = $this->getCacheKeyPrefix($time);
            foreach ($edids['edids'] as $edid) {
                $toStore[$prefix . $edid['edid']] = $edid['geometry'];
            }
            $this->setEdidCache($toStore);
        }
    }

    public function saveGeometryOfRoads(array $roads, $time)
    {
        $this->fileCache->prepareFileCache($time);
        $roadsToSend = $this->fileCache->initData($roads);

        $this->useCache = false;
        $data = $this->edidLookup($roadsToSend, $time);
        $this->useCache = true;

        $filesToSave = array();

        //saving found geometries
        if (array_key_exists('edids', $data)) {
            foreach ($data['edids'] as $edid) {
                if ($edid["geometry"] != '' && count($edid["geometry"])) {
                    $filesToSave[$this->fileCache->getRouteOfEdid($edid['edid'])][strtoupper($edid['edid'])] = $edid['geometry'];
                }
            }
        }

        //saving Invalid directed edids with the other's in direction
        if (array_key_exists('invalids', $data)) {
            foreach ($data['invalids'] as $invalidEdid) {
                //search for realted road
                $direction = substr($invalidEdid, -1);
                $oppositeDirection = $direction == '+' ? '-' : '+';
                $relatedRoadEdid = substr($invalidEdid, 0, -1) . $oppositeDirection;
                $route = $this->fileCache->getRouteOfEdid($invalidEdid);
                if (array_key_exists(strtoupper($relatedRoadEdid), $filesToSave[$route])) {
                    $relatedRoadGeometry = $filesToSave[$route][strtoupper($relatedRoadEdid)];
                    $filesToSave[$route][strtoupper($invalidEdid)] = $relatedRoadGeometry;
                }
            }
        }

        foreach ($filesToSave as $fileName => $dataToSave) { // save files
            $this->fileCache->writeCacheFile($dataToSave, $this->fileCache->getCurrentSubDir() . '/' . strtolower($fileName) . '.json');
        }

        unset($data['edids']);
        return $data;
    }
}
