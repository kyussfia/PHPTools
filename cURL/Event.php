<?php

namespace App\Util\cURL;

use Symfony\Component\EventDispatcher\Event as SymfonyEvent;

class Event extends SymfonyEvent
{
    public $response;
    public $request;
    public $queue;
}