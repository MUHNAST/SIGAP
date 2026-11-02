<?php

namespace App\Helpers;

class DeviceHelper
{
    public static function fingerprint(): string
    {
        return hash(
            'sha256',
            implode('|', [
                request()->userAgent(),
                request()->header('Accept-Language'),
                request()->header('Sec-CH-UA'),
                request()->header('Sec-CH-UA-Platform'),
            ])
        );
    }
}
