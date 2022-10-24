<p align="center">
  <img src="logo.png" alt="PayFast Payment Gateway" width="300"/><br/>
  <!-- <h3 align="center">Payfast</h3> -->
</p>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/zfhassaan/Payfast.svg?style=flat-square)](https://packagist.org/packages/zfhassaan/payfast)
[![Total Downloads](https://img.shields.io/packagist/dt/zfhassaan/Payfast.svg?style=flat-square)](https://packagist.org/packages/zfhassaan/payfast)

<h4> Disclaimer </h4>
This is unofficial Payfast API Payment Gateway. Payfast is not responsible for anything that happens on your website / Application. This repository  is only created to help developers in streamlining the integration process. You can Review the Official Payment Gateway <a href="https://gopayfast.com/docs/#preface" >here.</a> 

This Package only covers direct checkout and hosted checkout process. There's no Subscription option enabled yet it'll be added in the next build.


#### About
This document contains detailed explanation about how to integrate with Payfast API's Based transactions functionality. This document also contains the details for online transaction. 
<small>v1.0.0</small>

#### Intended Audience 
This document is for merchants acquires and developers who want to integrate with Payfast to perform a API's based Transaction. 

#### Integration Scope
The merchant will implement all ecommerce functionality. PayFast service (PayFast) will be used only payment processing. 

#### API End Points
This section contains the details of all APIs provided by Payfast. These APIs could be called by the merchants, acquirers and/or aggregators. These APIs are based on REST architecture and serve standard HTTP codes for the response payload. 

#### Integration Prerequisites
Merchants will be registered on PayFast prior to integration. After merchant sign up for PayFast account, following two unique values will be provided to merchant to operate: *Merchant_ID* and *Secured_key* , these keys are used to get a one-time authentication token, which is used to authenticate payment requests to the "PayFast"payment gateway.

#### Installation
You can install the package via composer 

````
composer require zfhassaan/payfast
````

#### Set .env configurations

```
PAYFAST_API_URL=
PAYFAST_SANDBOX_URL=
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

The Direct Checkout and Hosted checkout credentials can be obtained from <a href="https://gopayfast.com/">Payfast</a> The following are direct checkout methods that can be used with PCIDSS certified websites.

##### What is PCI DSS Certified.
PCI certification ensures the security of card data at your business through a set of requirements established by the PCI SSC. These include a number of commonly known best practices, such as: Installation of firewalls. Encryption of data transmissions, use of anti-virus software. In addition businesses must restrict access to cardholder data and monitor access to network resources.

PCI-compliant security provides a valuable asset that informs customers that your business is safe to transact with. Conversely, the cost of noncompliance, both in monetary and reputational terms, should be enough to convince any business owner to take data security seriously.

A data breach that reveals sensitive customer information is likely to have severe repercussions on an enterprise. A breach may result in fines from payment card issuers, lawsuits, diminished sales and a severely damaged reputation.

After experiencing a breach, a business may have to cease accepting credit card transactions or be forced to pay higher subsequent charges than the initial cost of security compliance. The investment in PCI security procedures goes a long way toward ensuring that other aspects of your commerce are safe from malicious online actors.

##### Hosted Checkout 
The Hosted checkout requires to follow following steps;
1. Get Authentication Token from Payfast
2. Create signature with md5 standard `md5($merchant_id.':' . $merchant_name.':'.$amount.':'.$order_id)`
3. Create Payload for website. The website payload will look something like this: 

```php 
...
...
$backend_callback = "signature=".$signature."&order_id=".$order_id;
...
...
$payload = array(
            'MERCHANT_ID' => $merchant_id, // Merchant ID received from Payfast
            'MERCHANT_NAME' => $merchant_name, // Merchant Name registered with Payfast.
            'TOKEN' => $ACCESS_TOKEN, // Access Token received from Payfast.
            'PROCCODE' => 00, // status code default is 00
            'TXNAMT' => $amount, // Transaction Amount or total amount
            'CUSTOMER_MOBILE_NO' => $mobile, // Customer Mobile Number
            'CUSTOMER_EMAIL_ADDRESS' => $email, // Customer Email address
            'SIGNATURE' => $signature, // Signature as described in above step 2.
            'VERSION' => 'WOOCOM-APPS-PAYMENT-0.9', // Optional
            'TXNDESC' => 'Products purchased from ' .$merchant_name, // Transaction Description to show on website
            'SUCCESS_URL' => urlencode($successUrl), // Success URL where to redirect user after success
            'FAILURE_URL' => urlencode($failUrl), // Failure URL where to redirect user after failure
            'BASKET_ID' => $order_id, // Order ID from Checkout Page.
            'ORDER_DATE' => date('Y-m-d H:i:s', time()), // Order Date 
            'CHECKOUT_URL' => urlencode($backend_callback), // Encrypted Checkout URL
        );
		

```

4. Submit it on Payfast provided URL. 
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
 * You can Show Proceed to Payment Screen or Complete Transaction Screen.
 * Step 2
 */
public function callback(Request $request) {
    return response()->json($request->all());
}

/**
 * Send a request again with Required Params and complete the transaction
 * Proceed to Payment and complete Transaction
 * Step 3 
 */
public function proceed(Request $request) {
  $payfast = new Payfast();
  $response = $payfast->initiate_transaction($request);
  return $response;
}


/**
 * Mobile Wallet Account Initiate Transaction 
 * This is demo function for Easy Paisa. 
 * 
 */

public function payfast(Request $request)
{
    $payfast = new Payfast();
    $response = $payfast->getToken();
    if($response != null && $response->code == "00" ){
        $payfast->setAuthToken($response->token);
    } else {
        abort(403, 'Error: Auth Token Not Generated.');
    }
    $show_otp = $payfast->wallet($request->all());
    return $show_otp;
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
