<?php

namespace App\Http\Controllers;

//import Snap midtrans
use Midtrans\Snap;

//import model Donation
use App\Models\Donation;

//import Str
use Illuminate\Support\Str;

//import request
use Illuminate\Http\Request;

class DonationController extends Controller
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
     * @return void
     */
    public function index()
    {
        //get data donations
        $donations = Donation::latest()->paginate(10);

        //render view
        return view('donations.index', compact('donations'));
    }

    /**
     * create
     *
     * @return void
     */
    public function create()
    {
        //render view
        return view('donations.create');
    }

    /**
     * store
     *
     * @param  mixed $request
     * @return void
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required',
            'email'     => 'required|email',
            'amount'    => 'required',
            'note'      => 'required',
        ]);

        //insert donation to database
        $donation = Donation::create([
            'invoice'   => 'INV-'.Str::upper(Str::random(5)),
            'name'      => $request->name,
            'email'     => $request->email,
            'amount'    => $request->amount,
            'note'      => $request->note,
            'status'    => 'PENDING',
        ]);

        // Buat transaksi ke midtrans kemudian save snap tokennya.
        $payload = [
            'transaction_details' => [
                'order_id'      => $donation->invoice,
                'gross_amount'  => $donation->amount,
            ],
            'customer_details' => [
                'first_name'       => $donation->name,
                'email'            => $donation->email,
            ]
        ];

        //create snap token
        $snapToken = Snap::getSnapToken($payload);
        $donation->snap_token = $snapToken;
        $donation->save();

        if ($donation) {
            return redirect()->route('donations.index')->with('success', 'Donation created successfully');
        }
    }
}