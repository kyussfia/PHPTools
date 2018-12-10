<?php

namespace AppBundle\Util\Topo;

use AppBundle\Util\HttpClient\HttpClient;

class TopoApi
{
    private $httpClient;
    private $apiKey;
    private $apiUrl;
    private $logger;
    private $timeout;

    public function __construct(HttpClient $httpClient, array $params, $logger)
    {
        $this->httpClient = $httpClient;

        $this->apiKey = $params['api_key'];
        $this->apiUrl = $params['api_url'];
        $this->timeout = $params['timeout'];
        $this->logger = $logger;
    }

    /**
     * MilestoneRoute api
     *
     * example call: http://178.248.200.71:19879/routecalc/milestoneRoute/?route=82&from=54+0&to=50
     * example reply: {"edids":["82u45k512m+","82u45k512m-","82u51k2727m+","82u51k2727m-"],"error":"","geoms":[[[47.4822654724121094,17.7871570587158203],[47.4822654724121094,17.7871570587158203],[47.4827232360839844,17.7868576049804688],[47.4833641052246094,17.7864799499511719],[47.4845237731933594,17.7856178283691406],[47.4864807128906250,17.7841587066650391],[47.4897575378417969,17.781738281250],[47.4902534484863281,17.7813835144042969],[47.4907264709472656,17.7810993194580078],[47.4929275512695312,17.7801494598388672],[47.4942283630371094,17.7787990570068359],[47.4945983886718750,17.7784156799316406],[47.497985839843750,17.7743473052978516],[47.4987907409667969,17.7733783721923828],[47.4995536804199219,17.77246093750],[47.50,17.7719268798828125],[47.500549316406250,17.7712707519531250],[47.5051536560058594,17.7657814025878906],[47.5065994262695312,17.7644100189208984],[47.5079193115234375,17.7632942199707031],[47.5081558227539062,17.7630939483642578],[47.5085067749023438,17.7627906799316406],[47.5101509094238281,17.7613773345947266],[47.510925292968750,17.7606830596923828],[47.5113906860351562,17.7602233886718750],[47.5116386413574219,17.7599563598632812],[47.5119323730468750,17.7596397399902344],[47.5135383605957031,17.7572517395019531],[47.5141067504882812,17.7563858032226562],[47.5143661499023438,17.7560081481933594],[47.5149955749511719,17.7553329467773438],[47.5153427124023438,17.7550354003906250],[47.5159568786621094,17.7545070648193359],[47.5167083740234375,17.7538547515869141],[47.5169143676757812,17.7536754608154297],[47.5171852111816406,17.7534809112548828],[47.5175933837890625,17.7532253265380859],[47.5181694030761719,17.7527790069580078],[47.5183563232421875,17.7526111602783203],[47.5185623168945312,17.7524032592773438],[47.5186882019042969,17.7522621154785156],[47.5190315246582031,17.7518577575683594],[47.5193176269531250,17.7514991760253906],[47.5193176269531250,17.7514991760253906]],[[47.5193176269531250,17.7514991760253906],[47.5193176269531250,17.7514991760253906],[47.5190315246582031,17.7518577575683594],[47.5186882019042969,17.7522621154785156],[47.5185623168945312,17.7524032592773438],[47.5183563232421875,17.7526111602783203],[47.5181694030761719,17.7527790069580078],[47.5175933837890625,17.7532253265380859],[47.5171852111816406,17.7534809112548828],[47.5169143676757812,17.7536754608154297],[47.5167083740234375,17.7538547515869141],[47.5159568786621094,17.7545070648193359],[47.5153427124023438,17.7550354003906250],[47.5149955749511719,17.7553329467773438],[47.5143661499023438,17.7560081481933594],[47.5141067504882812,17.7563858032226562],[47.5135383605957031,17.7572517395019531],[47.5119323730468750,17.7596397399902344],[47.5116386413574219,17.7599563598632812],[47.5113906860351562,17.7602233886718750],[47.510925292968750,17.7606830596923828],[47.5101509094238281,17.7613773345947266],[47.5085067749023438,17.7627906799316406],[47.5081558227539062,17.7630939483642578],[47.5079193115234375,17.7632942199707031],[47.5065994262695312,17.7644100189208984],[47.5051536560058594,17.7657814025878906],[47.500549316406250,17.7712707519531250],[47.50,17.7719268798828125],[47.4995536804199219,17.77246093750],[47.4987907409667969,17.7733783721923828],[47.497985839843750,17.7743473052978516],[47.4945983886718750,17.7784156799316406],[47.4942283630371094,17.7787990570068359],[47.4929275512695312,17.7801494598388672],[47.4907264709472656,17.7810993194580078],[47.4902534484863281,17.7813835144042969],[47.4897575378417969,17.781738281250],[47.4864807128906250,17.7841587066650391],[47.4845237731933594,17.7856178283691406],[47.4833641052246094,17.7864799499511719],[47.4827232360839844,17.7868576049804688],[47.4822654724121094,17.7871570587158203],[47.4822654724121094,17.7871570587158203]]]}
     *
     * @oaram string $route
     * @param int $from
     * @param int $to
     * @param int $side
     * @param int $time
     */
    public function milestoneRoute($route, $from, $to, $side, $time)
    {
        $params = $this->getParams(array(
            "route" => $route,
            "from" => $from,
            "to" => $to,
            "side" => $side,
            "time" => $time
        ));

        $response = $this->httpClient->get(
            $this->apiUrl . 'routecalc/milestoneRoute/',
            $params,
            $this->timeout
        )->getResponse();

        $checkedResponse = $this->checkTopoResponse($response);

        if (!empty($checkedResponse['error'])) {
            throw TopoException::unknownError();
        } else {
            unset($checkedResponse['error']); // remove empty error

            //rename key for ITS
            $checkedResponse["geometry"] = $checkedResponse["geoms"];
            unset($checkedResponse["geoms"]);

            //sorting data (edids)
            $checkedResponse["segments"] = is_null($checkedResponse["edids"]) ? null : array_map(
                function ($elem) {
                    return array("id" => $elem);
                },
                $checkedResponse["edids"]
            );

            unset($checkedResponse["edids"]);
        }

        return $checkedResponse;
    }

    public function edidCut($marker1Lat, $marker1Long, $marker2Lat, $marker2Long, $time)
    {
        $params = $this->getParams(array(
            'lat1' => $marker1Lat,
            'long1' => $marker1Long,
            'lat2' => $marker2Lat,
            'long2'=>$marker2Long,
            'time'=>$time,
            'dummy' => 'true'
        ));

        $response = $this->httpClient->get(
            $this->apiUrl . 'routecalc/edidcut/',
            $params,
            $this->timeout
        )->getResponse();

        $arr = $this->checkTopoResponse($response);

        $resp = array('success' => true);
        $resp['edid'] = $arr['edid'];

        return $resp;
    }

    /**
     * http://178.248.200.71:19879/routecalc/edidmilestoneinfo/?lat=47.51425&long=22.38236&edid=49U46K446M&api_key=TY3wKZ9Zlf4UcDWPnTlTtR9pNkYroIPk
     *
     * @param double $lat
     * @param double $long
     * @param string $edid
     */
    public function mileStoneInfo($lat, $long, $edid, $time)
    {
        $params = $this->getParams(array(
            'lat' => $lat,
            'long' => $long,
            'edid' => $edid,
            'time' => $time,
            'dummy' => 'true'
        ));

        $response = $this->httpClient->get(
            $this->apiUrl . 'routecalc/edidmilestoneinfo/',
            $params,
            $this->timeout
        )->getResponse();

        $arr = $this->checkTopoResponse($response);
        $resp = $arr;
        $resp['success'] = true;
        return $resp;
    }

    public function edidLookup($edidsToProcess, $time)
    {
        $resp = array();
        if (!empty($edidsToProcess))
        {
            $resp = array('success' => true);
            if (is_string($edidsToProcess)) // single road
            {
                $chunks = array($edidsToProcess); //an array with one element (string-EDID)
            }
            elseif (is_array($edidsToProcess))
            {
                $chunks = $this->splitEdidList($edidsToProcess);
            }

            $queue = new \AppBundle\Util\cURL\RequestQueue();
            $queue->setDefaultOptions(array(
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_RETURNTRANSFER => true
            ));

            if (!$queue->hasListeners('complete')) { // check existance to avoid duplicated responses
                $queue->addListener('complete', function (\AppBundle\Util\cURL\Event $event) use (&$resp) {
                    if (!$event->response->hasError()) {
                        $checkedResponse = $this->checkTopoResponse($event->response->getContent());
                        if (!empty($checkedResponse['error'])) {
                            $resp['error'][] = $checkedResponse['error'];
                        }

                        if (!empty($checkedResponse['invaliddir'])) {
                            if (!array_key_exists('invalids', $resp)) {
                                $resp['invalids'] = array();
                            }
                            $resp['invalids'] = array_merge($resp['invalids'], $checkedResponse['invaliddir']);
                        }
                        if (!empty($checkedResponse['unknownedids'])) {
                            if (!array_key_exists('unknown', $resp)) {
                                $resp['unknown'] = array();
                            }
                            $resp['unknown'] = array_merge($resp['unknown'], $checkedResponse['unknownedids']);
                        }

                        foreach ($checkedResponse['results'] as $geoData) {
                            $geometry = array();
                            foreach ($geoData['geometry'] as $key => $coordinates) {
                                $geometry[] = array_map(function($latLong) {
                                    return array_values($latLong);
                                }, $coordinates);
                            }

                            $resp['edids'][] = array('edid' => $geoData['edid'], 'geometry' => $geometry);
                        }
                    } else {
                        throw $event->response->getError();
                    }
                });
            }

            foreach ($chunks as $chunk) {
                $params = $this->getParams(array(
                    'edids' => implode(",", $chunk),
                    'time' => $time,
                    'dummy' => 'true'
                ));
                $request =  new \AppBundle\Util\cURL\Request($this->apiUrl . 'routecalc/edidlookup/?' . $params);
                $queue->attach($request);
            }

            $queue->send();
        }

        return $resp;
    }

    public function routeCalc($geo, $time)
    {
        $paramsArr = array(
            'vehicle' => 3, //J4
            'aurocat' => 1, 'motorway' => 1, 'ferry' => 0,
            'method' => 'SHORT', 'vehicleclass' => 'TRUCK',
            'weight' => 0, 'weight_axle' => 0,
            'height' => 0, 'length' => 0,
            'time' => $time
        );

        $params = $this->getParams(array_merge($paramsArr, $geo));
        $response = $this->httpClient->get($this->apiUrl . 'routecalc/route/', $params, $this->timeout)->getResponse();

        $arr = $this->checkTopoResponse($response);

        $resp = array();
        $resp['segments'] = $this->getResponseField($arr, 'paysegments');
        $resp['time'] = $this->getResponseField($arr, 'time');
        $resp['length'] = $this->getResponseField($arr, 'length');
        $geometry = $this->getResponseField($arr, 'geometry');
        foreach ($geometry as $g) {
            $resp['geometry'][] = array($g['lat'],$g['long']);
        }
        return $resp;
    }

    public function invGeocode($latitude, $longitude, $time)
    {
        // lat, long, time
        $params = $this->getParams(array(
            'lat' =>$latitude,
            'long' =>$longitude,
            'time' =>$time
        ));

        $response = $this->httpClient->get(
            $this->apiUrl . 'routecalc/invGeocode/',
            $params,
            $this->timeout
        )->getResponse();

        $arr = $this->checkTopoResponse($response);

        return $arr;
    }

    public function mapVersions()
    {
        $params = $this->getParams(array());
        $response = $this->httpClient->get(
            $this->apiUrl . 'routecalc/edidversions/',
            $params,
            $this->timeout
        )->getResponse();

        $arr = $this->checkTopoResponse($response);
        return $arr['versions'];
    }

    public function mapVersion($version)
    {
        $params = $this->getParams(array('version'=>$version,'dummy'=>'true'));
        $response = $this->httpClient->get(
            $this->apiUrl . 'routecalc/edidlist/',
            $params,
            $this->timeout
        )->getResponse();
        $arr = $this->checkTopoResponse($response);
        return $arr;
    }

    protected function splitEdidList($edids)
    {
        if (is_array($edids)) {
            return array_chunk($edids, 49);
        }
        return array_chunk(explode(",", $edids), 49);
    }

    protected function getResponseField($respData, $index)
    {
        if (isset($respData[$index])) {
            return $respData[$index];
        }
        throw new TopoException('json.unknown.field');
    }

    /**
     *
     * @param array $arrayParams
     * @param boolean $api_key
     * @return string
     */
    protected function getParams(array $arrayParams)
    {
        return http_build_query($arrayParams) . '&api_key=' . $this->apiKey;
    }

    protected function decodeResponse($response)
    {
        $arr = json_decode($response, true);
        if ($arr == null) {
            throw new \RuntimeException('JSonParseError in TOPO response');
        }
        return $arr;
    }

    protected function checkTopoResponse($response)
    {
        $arr = $this->decodeResponse($response);

        if (isset($arr['ok']) && $arr['ok'] === false ||
            isset($arr['error']) && !empty($arr['error']) &&
            (!isset($arr['results']) || !count($arr['results']))
            ) {
            if (($found = strpos($arr['error'], "NA: no segment")) !== false) {
                throw TopoException::noSegmentsFound();
            }
            if (($found = strpos($arr['error'], "NA: no EDID on segments")) !== false  ||
                ($found = strpos($arr['error'], "NA: No EDID cutted")) !== false  ||
                ($found = strpos($arr['error'], "NA: EDID not found")) !== false  ||
                ($found = strpos($arr['error'], "NA edids:")) !== false
            ) {
                throw TopoException::notFoundEdid();
            }
            if ($found = strpos($arr['error'], "NA: Multiple EDID cutted")  !== false) {
                throw TopoException::multipleCutted();
            }
            if ($found = strpos($arr['error'], "BAD_PARAMETERS")  !== false) {
                throw TopoException::badParameters();
            }
            if ($found = strpos($arr['error'], "CANT_FIND_TO")  !== false) {
                throw TopoException::cantFindTo();
            }
            if ($found = strpos($arr['error'], "CANT_FIND_FROM")  !== false) {
                throw TopoException::cantFindFrom();
            }
            throw TopoException::unknownError();
        }
        return $arr;
    }


}
