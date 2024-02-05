<?php

namespace zfhassaan\Payfast\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as JResponse;


class Utility {

    /**
     * Logs the Data to a channel, if the channel is not available then it creates the channel and log
     * the required data in the respective directory. This is handy in logging the data when you want to
     * use different types of channels in logging the data.
     *
     * @param string $channel
     * @param string $identifier
     * @param mixed $data
     * @return void
     */
    public static function LogData(String $channel, String $identifier, mixed $data):void
    {
        // Check if the specified channel exists in the logging configuration
        if (!config("logging.channels.$channel")) {
            // Create a new channel configuration if it doesn't exist
            config(["logging.channels.$channel" => [
                'driver' => 'daily',
                'path' => storage_path("logs/$channel/$channel.log"),
                'level' => 'debug',
            ]]);

            // Reconfigure the logger with the new channel
            Log::channel($channel);
        }
        // Log the data
        Log::channel($channel)->info('===== ' . $identifier . ' ====== ' . json_encode($data));
    }

    /**
     * Return Success Message as a common response.
     *
     * @param mixed $data
     * @param mixed $code
     * @param int $status
     * @return JsonResponse
     */
    public static function returnSuccess(mixed $data, mixed $code = [], int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $data,
            'code' => $code,
        ],$status);
    }

    /**
     * Return Error From The response with a Log in common laravel.log file.
     * This also enables to debug the log on detailed level.
     * @param mixed $message
     * @param string $code
     * @param int $status
     * @return JsonResponse
     */
    public static function returnError(mixed $message, string $code = '', int $status = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        self::LogData('Payfast','========== Pay fast ERROR ==========',$message);

        return response()->json([
            'status' => false,
            'message' => $message,
            'code' => $code,
        ],$status);
    }

    /**
     * This function returns the Error Messages returned from the Payfast.
     */
    public static function PayfastErrorCodes($code): JsonResponse
    {

        $errorDescription = [
            '00' => 'Processed OK',
            '001' => 'Pending',
            '002' => 'Payfast Time out',
            '30' => 'Account type is required',
            '3'   => 'You have entered an Inactive Account',
            '97' => 'Dear Customer, you have an insufficient Balance to proceed',
            '106' => 'Dear Customer, Your transaction Limit has been exceeded please contact your bank',
            '14'    => 'Entered details are Incorrect',
            '55' => 'You have entered an Invalid OTP/PIN',
            '54' =>  'Card Expired',
            '90' => 'SSL is required. No SSL Found',
            '13' => 'You have entered an Invalid Amount',
            '126' => 'Dear Customer your provided Account details are Invalid',
            '75' => 'Maximum PIN Retries has been exceeded',
            '401' => 'You\'re not Authorized',
//            14 Dear Customer, You have entered an In-Active Card number
            '15' => 'Dear Customer, You have entered an In-Active Card number',
            '42' => 'Dear Customer, You have entered an invalid CNIC',
            '423' => 'Dear Customer, We are unable to process your request at the moment please try again later',
            '41' => 'Dear Customer, entered details are Mismatched',
            '801'  => '{0} is your PayFast OTP (One Time Password). Please do not share with anyone.',
            '802' => 'OTP could not be sent. Please try again later.',
            '803' => 'OTP has been sent to your email address',
            '804' => 'OTP has been sent to your mobile number',
            '805' => 'OTP Verified',
            '806' => 'OTP could not be verified',
            '807' => 'Too many attempts. Please try again later in few minutes',
            '808' => 'Passwords do not match',
            '809' => 'Invalid Password',
            '810' => 'Password could not be changed',
            '811' => 'Password changed successfully',
            '812' => 'Request could not be validated. Please try again',
            '813' => 'Email address already registered',
            '850' => 'OTP not required because issuer manages OTP itself.',
            '851' => 'OTP required for permanent token',
            '79'  => 'Alternate Success response',
            '9000' => 'Rejected by FRMS'
        ];

        if (array_key_exists($code, $errorDescription)) {
            return JResponse::json(['error_description' => $errorDescription[$code]], 200);
        } else {
            return JResponse::json(['error_description' => 'Unknown Error Code'], 406);
        }
    }
}
