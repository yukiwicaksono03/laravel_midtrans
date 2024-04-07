<?php

namespace App\Http\Controllers\Api;

//import model donation
use App\Models\Donation;

//import request
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class CallbackController extends Controller
{   
    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        // Set midtrans configuration
        \Midtrans\Config::$serverKey    = config('services.midtrans.serverKey');
        \Midtrans\Config::$isProduction = config('services.midtrans.isProduction');
        \Midtrans\Config::$isSanitized  = config('services.midtrans.isSanitized');
        \Midtrans\Config::$is3ds        = config('services.midtrans.is3ds');
    }  

    /**
     * index
     *
     * @param  mixed $request
     * @return void
     */
    public function index(Request $request)
    {
        $payload      = $request->getContent();
        $notification = json_decode($payload);
      
        $validSignatureKey = hash("sha512", $notification->order_id . $notification->status_code . $notification->gross_amount . config('services.midtrans.serverKey'));

        if ($notification->signature_key != $validSignatureKey) {
            return response(['message' => 'Invalid signature'], 403);
        }

        $transaction  = $notification->transaction_status;
        $type         = $notification->payment_type;
        $orderId      = $notification->order_id;
        $fraud        = $notification->fraud_status;

        //data donation
        $data_donation = Donation::where('invoice', $orderId)->first();

        if ($transaction == 'capture') {
 
            // For credit card transaction, we need to check whether transaction is challenge by FDS or not
            if ($type == 'credit_card') {

              if($fraud == 'challenge') {
                
                /**
                *   update invoice to pending
                */
                $data_donation->update([
                    'status' => 'PENDING'
                ]);

              } else {
                
                /**
                *   update invoice to success
                */
                $data_donation->update([
                    'status' => 'SUCCESS'
                ]);

              }

            }

        } elseif ($transaction == 'settlement') {

            /**
            *   update invoice to success
            */
            $data_donation->update([
                'status' => 'SUCCESS'
            ]);


        } elseif($transaction == 'pending'){

            
            /**
            *   update invoice to pending
            */
            $data_donation->update([
                'status' => 'PENDING'
            ]);


        } elseif ($transaction == 'deny') {

            
            /**
            *   update invoice to failed
            */
            $data_donation->update([
                'status' => 'FAILED'
            ]);


        } elseif ($transaction == 'expire') {

            
            /**
            *   update invoice to expired
            */
            $data_donation->update([
                'status' => 'EXPIRED'
            ]);


        } elseif ($transaction == 'cancel') {

            /**
            *   update invoice to failed
            */
            $data_donation->update([
                'status' => 'FAILED'
            ]);

        }

    }
}