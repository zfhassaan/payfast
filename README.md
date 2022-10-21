<p align="center">
  <img src="logo.png" alt="PayFast Payment Gateway" width="300"/><br/>
  <!-- <h3 align="center">Payfast</h3> -->
</p>


[![Latest Version on Packagist](https://img.shields.io/packagist/v/zfhassaan/Payfast.svg?style=flat-square)](https://packagist.org/packages/zfhaisssaan/alfa)
[![Total Downloads](https://img.shields.io/packagist/dt/zfhassaan/Payfast.svg?style=flat-square)](https://packagist.org/packages/zfhassaan/alfa)


<h4> Disclaimer </h4>
This is unofficial Payfast API Payment Gateway. Payfast is not responsible for anything that happens on your website / Application. This repository  is only created to help developers in streamlining the integration process. You can Review the Official Payment Gateway <a href="https://gopayfast.com/docs/#preface" >here.</a> 

#### About
This document contains detailed explanation about how to integrate with Payfast API's Based transactions functionality. This document also contains the details for online transaction. 

#### Intended Audience 
This document is for merchants acquires and developers who want to integrate with Payfast to perform a API's based Transaction. 

#### Integration Scope
The merchant will implement all ecommerce functionality. PayFast service (PayFast) will be used only payment processing. 

#### API End Points
This section contains the details of all APIs provided by Payfast. These APIs could be called by the merchants, acquirers and/or aggregators. These APIs are based on REST architecture and serve standard HTTP codes for the response payload. 

#### Installation
You can install the package via composer 

````
composer require zfhassaan/php-payfast
````

#### Set .env configurations

```
PAYFAST_API_URL=
PAYFAST_GRANT_TYPE=
PAYFAST_MERCHANT_ID=
PAYFAST_MODE=
PAYFAST_SECURED_KEY=
PAYFAST_RETURN_URL=
```

#### configuration
Add These files in `app/config.php`

```php 
  /*
    * Package Service Providers...
    */

  \zfhassaan\Payfast\PayfastServiceProvider::class,
```


and also in alias in `app/config.php`

```php 
  'aliases' => Facade::defaultAliases()->merge([
        'Payfast' => \zfhassaan\Payfast\Payfastfacade::class,
    ])->toArray(),
```

#### Steps
##### Direct Checkout
1. Get Authentication Token
2. Validate Customer Information.
3. Initiate Transaction

##### Hosted Checkout
1. Get Authentication Token
2. Initiate Transaction on Payfast Page.

The Direct Checkout and Hosted checkout credentials can be obtained from <a href="https://gopayfast.com/">Payfast</a>

#### Usage 

```php 
use zfhassaan\Payfast\Payfast;
...
...

/**
 * Validate Customer and get OTP Screen.
 * Step 1
 */
public function checkout(Request $request) {
  $payfast = new Payfast();
  $response = $payfast->getToken();
  if($response != null && $response->code == "00" ){
      $payfast->setAuthToken($response->token);
  } else {
      abort(403, 'Error: Auth Token Not Generated.');
  }
  $show_otp = $payfast->customer_validate($request->all());
  return $show_otp;
}

/**
 * Receive 3ds PaRes from Callback. 
 * This will be called on Callback from OTP Screen.
 * You can Show Proceed to Payment Screen or Complete Transaction Screen Here.
 * Step 2
 */
public function callback(Request $request) {

    return response()->json($request->all());
}

/**
 * Proceed to Payment and complete Transaction
 * Step 3 
 */
public function proceed(Request $request) {
  $payfast = new Payfast();
  $response = $payfast->initiate_transaction($request);
  return $response;
}

```

#### Changelog
Please see Changelog for more information what has changed recently. 

#### Security 
The following lines are taken from [briandk](https://gist.github.com/briandk/3d2e8b3ec8daf5a27a62) repository for contributing in an open source projects.

**Great Bug Reports** tend to have:

- A quick summary and/or background
- Steps to reproduce
  - Be specific!
  - Give sample code if you can. An issue includes sample code that *anyone* with a base R setup can run to reproduce what I was seeing
- What you expected would happen
- What actually happens
- Notes (possibly including why you think this might be happening, or stuff you tried that didn't work)


#### License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
