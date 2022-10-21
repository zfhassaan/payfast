<?php 

namespace zfhassaan\Payfast;

use Illuminate\Support\Facades\Facade;

class PayFastFacade extends Facade
{
    /**
     * Get the registered name of the component
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'payfast';        
    }
}