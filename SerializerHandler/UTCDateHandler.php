<?php
namespace Slametrix\SerializerHandler;

use \JMS\Serializer\Context;
use \JMS\Serializer\VisitorInterface;
use \JMS\Serializer\XmlSerializationVisitor;

class UTCDateHandler extends \JMS\Serializer\Handler\DateHandler
{
    private $defaultFormat;
    private $xmlCData;

    public function __construct($defaultFormat = \DateTime::ISO8601, $defaultTimezone = 'UTC', $xmlCData = true)
    {
        $this->defaultFormat = $defaultFormat;
        $this->xmlCData = $xmlCData;
        parent::__construct($defaultFormat, $defaultTimezone, $xmlCData);
    }

    public function serializeDateTime(VisitorInterface $visitor, \DateTime $date, array $type, Context $context)
    {
        return $this->serializeDateTimeInterface($visitor, $date, $type, $context);
    }

    private function serializeDateTimeInterface(
        VisitorInterface $visitor,
        \DateTimeInterface $date,
        array $type,
        Context $context
    )
    {
        $format = $this->getFormat($type);
        $formatted = null;
        if ($date instanceof \DateTime || $date instanceof \DateTimeImmutable)
        {
            $originalTimeZone = $date->getTimeZone();
            $groups = array();
            if ( ! $context->attributes->get('groups') instanceof \PhpOption\None) {
                $groups = $context->attributes->get('groups')->get();
            }

            switch (true) {
                case in_array("utc", $groups):
                    $wannaBeTimeZone = new \DateTimeZone('UTC'); //if serialization rule has 'utc' then convert into that
                    break;
                case in_array("localtimezone", $groups):
                    $tz = date_default_timezone_get();
                    $wannaBeTimeZone = new \DateTimeZone($tz);
                    break;
                default:
                    $wannaBeTimeZone = $originalTimeZone;
            }

            $formatted = $date->setTimeZone($wannaBeTimeZone)->format($format);
        }
        else {
            $formatted = $date->format($format);
        }

        if ($visitor instanceof XmlSerializationVisitor && false === $this->xmlCData) {
            return $visitor->visitSimpleString($formatted, $type, $context);
        }

        if ('U' === $format) {
            return $visitor->visitInteger($formatted, $type, $context);
        }

        return $visitor->visitString($formatted, $type, $context);
    }

    /**
     * Copied from parent
     * @return string
     * @param array $type
     */
    private function getFormat(array $type)
    {
        return isset($type['params'][0]) ? $type['params'][0] : $this->defaultFormat;
    }
}