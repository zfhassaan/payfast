<?php

namespace zfhassaan\Payfast\Facade;

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

    public static function getToken(){
        return static::resolveFacadeInstance('payfast')->GetToken();
    }

    public static function refreshToken($token,$refresh_token){
        return static::resolveFacadeInstance('payfast')->RefreshToken($token,$refresh_token);
    }
}
