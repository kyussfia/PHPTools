<?php

namespace App\Util\Mnb;

class MnbSoapApi
{
    const mnbApiUri = 'http://www.mnb.hu/arfolyamok.asmx?wsdl';

    public $connection;

    public function __construct()
    {
        try
        {
            $this->connection = new \SoapClient(self::mnbApiUri);
        } catch (\Exception $e)
        {
            throw new \RuntimeException('Unable to create SoapClient! Error: ' . $e->getMessage());
        }
    }

    private function call(string $command, array $params)
    {
        if (empty($this->connection))
        {
            throw new \RuntimeException("Undefined SOAP Client to call!");
        }
        return $this->connection->__soapCall($command, $params);
    }

    private function getResponse(object $response, string $key) : string
    {
        if (!property_exists($response, $key))
        {
            throw new \InvalidArgumentException('Unable to get response on key: ' . $key);
        }
        return $response->$key;
    }

    private function parseXml(string $result) : \DOMDocument
    {
        $doc = new \DOMDocument;
        $doc->loadXML($result);
        return $doc;
    }

    private function filterDomDoc(\DOMDocument $dom, string $pathToData, array $filter) : \DOMNodeList
    {
        $filterString = $this->createXpathFilterString($filter);
        $query = "//" . $pathToData . $filterString;
        $xpath = new \DOMXPath($dom);
        return $xpath->query($query);
    }

    private function getData(\DOMDocument $dom, string $pathToData, array $filter, string $assocAttr, array $extraAttrs = array()) : array
    {
        $entries = $this->filterDomDoc($dom, $pathToData, $filter);
        return $this->loadDataToArray($entries, $assocAttr, $extraAttrs);
    }

    /**
     * @WARNING if $uniqueAssocKeyAttrName is not unique you may loss some data.
     *
     * @param \DOMNodeList $entries
     * @param string $uniqueAssocKeyAttrName
     * @param array $extraData
     * @return array
     */
    private function loadDataToArray(\DOMNodeList $entries, string $uniqueAssocKeyAttrName, array $extraData) : array
    {
        $isNumericKeys = empty($uniqueAssocKeyAttrName);
        $isExtraDataNeeded = !empty($extraData);
        $result = array();
        foreach ($entries as $entry)
        {
            if ($isNumericKeys) {
                $result[] = $entry->nodeValue;
            } elseif ($entry->hasAttribute($uniqueAssocKeyAttrName)) {
                if (!$isExtraDataNeeded) {
                    $data = $entry->nodeValue;
                } else {
                    $data = array("nodeValue" => $entry->nodeValue);
                    foreach ($extraData as $attr)
                    {
                        if (!$entry->hasAttribute($attr))
                        {
                            throw new \InvalidArgumentException('Attribute ' . $attr . ' does not exist for every node!');
                        }
                        $data[$attr] = $entry->getAttribute($attr);
                    }
                }
                $result[$entry->getAttribute($uniqueAssocKeyAttrName)] = $data;
            } else {
                throw new \InvalidArgumentException('Key attribute ' . $uniqueAssocKeyAttrName . ' does not exist for every node!');
            }
        }
        return $result;
    }

    private function createXpathFilterString(array $filter) : string
    {
        $baseString = "";
        $first = true;
        foreach ($filter as $key => $value)
        {
            if ($first)
            {
                $baseString = "[";
                $first = false;
            } else {
                $baseString .= " and ";
            }

            if (!is_array($value))
            {
                $value = array($value);
            }

            $size = count($value);

            if ($size > 1)
            {
                $baseString .= "(";
            }

            for ($i = 0; $i < $size; $i++)
            {
                if ($i > 0)
                {
                    $baseString .= " or ";
                }
                $baseString .= "@" . $key . "=" . "'" . $value[$i] . "'";
            }

            if ($size > 1)
            {
                $baseString .= ")";
            }
        }

        if (!empty($filter))
        {
            $baseString .= "]";
        }

        return $baseString;
    }

    public function getCurrentExchangeRates(array $filter = array())
    {
        $response = $this->call('GetCurrentExchangeRates', array());
        $response = $this->getResponse($response, 'GetCurrentExchangeRatesResult');
        $domDoc = $this->parseXml($response);
        return array(
            'date' => @array_pop(array_reverse(array_keys($this->getData($domDoc, 'MNBCurrentExchangeRates/Day', array(), 'date')))),
            'data' => $this->getData($domDoc, 'MNBCurrentExchangeRates/Day/Rate', $filter, 'curr', array('unit'))
        );
    }
}