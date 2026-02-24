<?php

namespace NobelzSushank\Bsad\Facades;

use Illuminate\Support\Facades\Facade;

class Bsad extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BsadConverter::class;
    }
}