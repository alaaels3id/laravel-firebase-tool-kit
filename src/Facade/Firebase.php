<?php

namespace Alaaelsaid\LaravelFirebaseToolKit\Facade;

use Illuminate\Support\Facades\Facade;

class Firebase extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'Firebase';
    }
}