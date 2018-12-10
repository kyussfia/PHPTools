<?php

namespace App\Csv;

class Csv
{
    protected $fileRealPath;

    protected $delimiter;

    protected $decimalPoint = '.';

    protected $thousandSep = ' ';

    private $file;

    private $opened = false;

    private function __construct(string $fileRealPath, string $delimiter, $src = null)
    {
        if (empty($fileRealPath))
        {
            throw new \InvalidArgumentException('Csv construct parameter fileRealPath can not be empty.');
        }

        if (empty($delimiter))
        {
            throw new \RuntimeException("Csv delimiter cannot be empty.");
        }
        $this->delimiter =  $delimiter;
        $this->fileRealPath = $fileRealPath;
        $this->opened = !empty($src);
        $this->file = $src;
    }

    public static function getBomTypes() : array
    {
        return BomType::getAll();
    }

    public static function createFromRealPath(string $fileRealPath, string $delimiter = ',')
    {
        return new self($fileRealPath, $delimiter);
    }

    public static function createNew(string $delimiter = ',')
    {
        return self::createFromResource(tmpfile(), $delimiter);
    }

    public static function createFromResource($src, string $delimiter = ',')
    {
        $meta = stream_get_meta_data($src);
        return new self($meta['uri'], $delimiter, $src);
    }

    private function writeContent($data)
    {
        fwrite($this->getFile(), BomType::BOM_UTF8 . $data);
        $this->rewind();
        return $this;
    }

    public function writeByLine($data)
    {
        fwrite($this->getFile(), BomType::BOM_UTF8);
        foreach ($data as $lineData)
        {
            $this->writeLine($lineData);
        }
        $this->rewind();
        return $this;
    }

    private function writeLine($lineData)
    {
        $lineData = $this->transform($lineData);
        fputcsv($this->getFile(), $lineData, $this->delimiter);
        return $this;
    }

    private function transform($data)
    {
        foreach ($data as $key => $field)
        {
            if (is_numeric($field) && !is_int($field))
            {
                $data[$key] = number_format($field, 3, $this->decimalPoint, $this->thousandSep);
            }
        }
        return $data;
    }

    public function setDecimalPoint(string $sign = '.')
    {
        $this->decimalPoint = $sign;
    }

    public function getFileSize()
    {
        return stat($this->fileRealPath)['size'];
    }

    private function getFile()
    {
        if (!$this->opened)
        {
            return $this->open();
        }
        return $this->file;
    }

    public function open()
    {
        if (!$this->opened)
        {
            $this->file = fopen($this->fileRealPath, 'r');
            $this->opened = true;

            if ($this->getFile() === false)
            {
                throw new \RuntimeException("Cannot open CSV.");
            }
        }

        return $this->getFile();
    }

    public function close()
    {
        if ($this->opened)
        {
            fclose($this->file);
        }
    }

    public function getLine(int $dataLength = 0)
    {
        return fgetcsv($this->getFile(), $dataLength, $this->delimiter);
    }

    private function rewind()
    {
        fseek($this->file, 0);
    }

    public function getContent()
    {
        return stream_get_contents($this->getFile());
    }
}