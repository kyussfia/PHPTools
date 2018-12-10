<?php

namespace App\Csv;


class CsvImport
{
    private $csv;

    private $header;

    private $currentLine = 0;

    private $inputBom;

    private $isBomChecked;

    /**
     * CsvImport constructor.
     * @param Csv $csv
     * @param bool $hasHeader
     */
    public function __construct(Csv $csv, $hasHeader = false)
    {
        $this->csv = $csv;
        $this->csv->open();

        if ($hasHeader)
        {
            $this->header = $this->readLine();
            $this->currentLine++;
        }

        $this->isBomChecked = false;
    }

    private function detectBom(array $line) : void
    {
        $boms = Csv::getBomTypes();

        $first = $line[0];

        $currentBoms = array_filter($boms, function ($sequence) use ($first) {
            return strpos($first, $sequence) === 0;
        }); //only used remains here

        $this->isBomChecked = true;
        $this->inputBom = (string) array_shift($currentBoms);
    }

    private function isBomDetected()
    {
        return !empty($this->inputBom);
    }

    private function stripBom(array& $line) : void
    {
        $line[0] = str_replace($this->inputBom, '', $line[0]);
    }

    /**
     * @param array $header
     */
    public function setHeader(array $header): void
    {
        if (empty($header))
        {
            throw new \RuntimeException("Empty CSV header causes empty data.");
        }

        foreach ($header as $head)
        {
            if (empty($head))
            {
                throw new \RuntimeException("Cannot use empty value as a header field.");
            }
        }

        $this->header = $header;
    }

    private function hasHeader()
    {
        return isset($this->header);
    }

    public function __destruct()
    {
        $this->csv->close();
    }

    /**
     * If useHeader is true, there are no parse logic implemented yet.
     * Only numeric index based parsing implemented.
     *
     * @param bool $useHeader
     * @return array
     */
    public function import($useHeader = false)
    {
        if ($useHeader && !$this->hasHeader())
        {
            $this->header = $this->readLine();
            $this->currentLine++;
        }
        return $this->readContent($useHeader);
    }

    private function readContent($useHeader)
    {
        $data = array();
        while (($line = $this->readLine()) !== false)
        {
            $this->currentLine++;
            if ($useHeader)
            {
                $oneLineData = $this->applyHeader($line);
                $oneLineData['lineNumber'] = $this->currentLine;
            }
            else {
                $oneLineData = $line;
                $oneLineData[] = $this->currentLine;
            }
            $data[] = $oneLineData;
        }

        return $data;
    }

    private function applyHeader($line)
    {
        $assoc = array();
        for ($i = 0; $i < count($this->header); $i++)
        {
            $assoc[$this->header[$i]] = $line[$i];
        }
        return $assoc;
    }

    private function readLine()
    {
        $line = $this->csv->getLine();
        if (!$this->isBomChecked)
        {
            if (is_array($line))
            {
                $this->detectBom($line);
                if ($this->isBomDetected())
                {
                    $this->stripBom($line);
                }
            }
        }
        return $line;
    }
}