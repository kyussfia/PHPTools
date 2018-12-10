<?php

namespace App\Csv;

class BomType
{
    private $refl;

    /**
     *  UTF-8 BOM sequence
     */
    const BOM_UTF8 = "\xEF\xBB\xBF";
    /**
     * UTF-16 BE BOM sequence
     */
    const BOM_UTF16_BE = "\xFE\xFF";
    /**
     * UTF-16 LE BOM sequence
     */
    const BOM_UTF16_LE = "\xFF\xFE";
    /**
     * UTF-32 BE BOM sequence
     */
    const BOM_UTF32_BE = "\x00\x00\xFE\xFF";
    /**
     * UTF-32 LE BOM sequence
     */
    const BOM_UTF32_LE = "\xFF\xFE\x00\x00";

    private static function reflect()
    {
        try
        {
            $instance = new static();
            if (empty($instance->refl))
            {
                $instance->refl = new \ReflectionClass(get_class($instance));
            }

            return $instance;
        }
        catch (\ReflectionException $e)
        {
            throw new \RuntimeException("Can't reflect class: " . get_class($instance));
        }
    }

    public static function getAll() : array
    {
        return self::reflect()->refl->getConstants();
    }
}