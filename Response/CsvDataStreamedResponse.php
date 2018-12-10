<?php

namespace AppBundle\Response;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvDataStreamedResponse
{
    private $batchSize = 1000;

    private $response;

    private $csvHeader;

    private $csvDelimiter = ';';

    private $csvRowTransformer;

    private $queryBuilder;

    private $hydrationMode;

    private $queryUseIterator = true;

    private $em;

    private $gcEnabled = false;

    public function __construct(\Doctrine\ORM\QueryBuilder $queryBuilder, \Doctrine\Common\Persistence\ObjectManager $em)
    {
        $this->em = $em;
        $this->queryBuilder = $queryBuilder;
        $this->setHydrationMode();
        $this->response = new StreamedResponse();
    }

    public function enableGc(bool $toggle = true)
    {
        $this->gcEnabled = $toggle;
    }

    public function useIterator(bool $use = true)
    {
        $this->queryUseIterator = $use;
    }

    public function setHydrationMode($hydrationMode = \Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY)
    {
        $this->hydrationMode = $hydrationMode;
    }

    public function setCsvHeader(array $header)
    {
        $this->csvHeader = $header;
    }

    public function setBatchSize(int $size)
    {
        if ($size <= 0) {
            throw new \InvalidArgumentException("Wrong batchsize! Must be positive integer, " . $size . " given!");
        }
        $this->batchSize = $size;
    }

    public function setRowTransformer(callable $transformer)
    {
        $this->csvRowTransformer = $transformer;
    }

    private function identicalRowToArray($value) : array
    {
        return array($value);
    }

    private function callback()
    {
        $f = fopen('php://output', 'w+');
        fputs($f, chr(0xEF).chr(0xBB).chr(0xBF)); // add BOM to fix UTF-8 in M$ Excel
        if (!empty($this->csvHeader)) {
            fputcsv($f, $this->csvHeader, $this->csvDelimiter);
        }

        $offset = 0;
        $loaded = $this->batchSize;

        if ($this->gcEnabled) {
            gc_enable();
        }

        while ($loaded >= $this->batchSize)
        {
            $loaded = 0;
            $this->queryBuilder->setFirstResult($offset);
            $this->queryBuilder->setMaxResults($this->batchSize);

            $iterator = $this->queryUseIterator ? $this->queryBuilder->getQuery()->iterate(null, $this->hydrationMode) : $this->queryBuilder->getQuery()->execute(null, $this->hydrationMode);

            foreach ($iterator as $result) {
                $loaded++;
                $data = $this->queryUseIterator ? $result[0] : $result; // data is wrapped in array
                $rows = call_user_func($this->csvRowTransformer ?? array($this, 'identicalRowToArray'), $data);
                foreach ($rows as $row) {
                    try {
                        fputcsv($f, $row, $this->csvDelimiter);
                    } catch (\Exception $e) {
                        throw new \RuntimeException("Unable to convert value to csv row string! Error: " . $e->getMessage());
                    }
                }
            }
            $this->em->clear();
            $offset += $this->batchSize;

            if ($this->gcEnabled) {
                gc_collect_cycles();
            }
        }

        fclose($f);
    }

    private function setHeaders(string $filename)
    {
        $this->response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $this->response->headers->set('Cache-Control', 'no-store, no-cache');
        $this->response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'.csv"');
    }

    public function getResponse(string $fileName)
    {
        ini_set('max_execution_time', 0);
        $this->response->setCallback(function() {
            $this->callback();
        });
        $this->response->setStatusCode(200);
        $this->setHeaders($fileName);
        return $this->response;
    }
}