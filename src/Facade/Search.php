<?php

namespace Jdkweb\Search\Facade;

use Illuminate\Support\Facades\Facade;

class Search extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'search';
    }
}
