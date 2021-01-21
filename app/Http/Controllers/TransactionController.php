<?php

namespace App\Http\Controllers;

use App\Libraries\Midtrans\Midtrans;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Seminar;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class TransactionController extends Controller
{
    public $midtrans;

    public function __construct()
    {
        $this->midtrans = new Midtrans(config('midtrans'));
    }

    public function order(Request $request)
    {
        $payload = $request->getContent();
        $postData = json_decode($payload, true);

        $this->validate($request->merge($postData), [
            'user_id' => 'required',
            'seminar_id' => 'required',
        ]);

        $seminar = Seminar::where(['uuid' => $postData['seminar_id']])->first();

        $order = new Order();
        $order->user_id = $postData['user_id'];
        $order->seminar_id = $seminar->id;
        $order->uuid = Uuid::uuid4();
        $order->save();

        return response()->json(['order' => Order::find($order->id)]);
    }

    public function payment(Request $request)
    {
        $payload = $request->getContent();
        $postData = json_decode($payload, true);

        $this->validate($request->merge($postData), [
            'order_id' => 'required',
            'payment_channel' => 'required',
        ]);

        $payment = new Payment($postData);
        $params = $payment->createTransactionData();

        return $this->midtrans->charge($params);
    }

    public function status($id)
    {
        $result = $this->midtrans->status($id);
        return response()->json(['data' => $result]);
    }

    public function notification(Request $request)
    {
        $payload = $request->getContent();
        $notification = json_decode($payload, true);

        $this->validate($request->merge($notification), [
            'order_id' => 'required',
            'transaction_id' => 'required',
            'merchant_id' => 'required',
        ]);

        $order = Order::where(['uuid' => $notification['order_id']])->first();
        if ($notification['transaction_status'] !== 'cancel') {
            switch ($notification['transaction_status']) {
                case 'settlement':
                case 'capture':
                    $order->status = 2;
                    break;
                case 'cancel':
                    $order->status = 3;
                    break;
                case 'expire':
                    $order->status = 4;
                    break;
                case 'refund':
                    $order->status = 5;
                    break;
                case 'deny':
                    $order->status = 6;
                    break;
            }

            $order->save();
        }

        return response()->json([
            'code' => 0,
            'message' => 'OK'
        ]);
    }
}
