<?php

declare(strict_types=1);

namespace KimJongOwn\OsrsWiki\Factories;

use KimJongOwn\OsrsWiki\Client;

class ClientFactory
{
    public function make(): Client
    {
        return new Client(new \GuzzleHttp\Client());
    }
}
