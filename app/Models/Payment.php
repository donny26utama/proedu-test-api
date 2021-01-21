<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    private $channel;
    private $order;
    private $grossAmount = 0;
    private $data = [];

    public function __construct($json)
    {
        $this->channel = $json['payment_channel'];
        $this->order = Order::where(['uuid' => $json['order_id']])->first();
    }

    public function createTransactionData()
    {
        $this->setPaymentChannel();
        $this->addItems();
        $this->additionalChannelParams();
        $this->setCustomerDetails();

        $this->data['transaction_details'] = [
            'order_id' => $this->order->uuid,
            'gross_amount' => $this->grossAmount,
        ];
        $this->order->status = 1; // waiting payment
        $this->order->save();

        return $this->data;
    }

    private function setCustomerDetails()
    {
        //
    }

    private function addItems()
    {
        $seminar = $this->order->seminar;

        $this->data['item_details'][] = [
            'id' => $seminar->uuid,
            'price' => $seminar->amount,
            'quantity' => 1,
            'name' => $seminar->title,
        ];

        $this->grossAmount = $seminar->amount * 1;
    }

    private function setPaymentChannel()
    {
        $this->data['payment_type'] = $this->channel;
        $this->order->payment_channel = $this->channel;
    }

    private function additionalChannelParams()
    {
        $func = sprintf('%sChannelParams', $this->channel);
        $this->$func();
    }

    private function gopayChannelParams()
    {
        $this->data[$this->channel] = [
            'enable_callback' => true,
            'callback_url' => 'http-our-callback-url',
        ];
    }
}
