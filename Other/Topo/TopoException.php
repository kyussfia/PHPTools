<?php

namespace AppBundle\Util\Topo;

use AppBundle\Exception\BusinessLogicException;

class TopoException extends BusinessLogicException
{
    protected $domain = 'Topo';

    public static function unknownError()
    {
        return new self('unknown');
    }
    
    public static function notFoundEdid()
    {
        return new self('not_found_edid');
    }

    public static function noSegmentsFound()
    {
        return new self('not_found_segment');
    }

    public static function multipleCutted()
    {
        return new self('multiple_cutted');
    }

    public static function cantFindTo()
    {
        return new self('cant_find_to');
    }

    public static function cantFindFrom()
    {
        return new self('cant_find_from');
    }
    public static function badParameters()
    {
        return new self('bad_parameters');
    }
}