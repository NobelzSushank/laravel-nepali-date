<?php

namespace NobelzSushank\Bsad\Facades;

use Illuminate\Support\Facades\Facade;
use NobelzSushank\Bsad\Converters\BsadConverter;

class NepaliDateConverter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BsadConverter::class;
    }
}