<?php

namespace App;

use App\Notifications\SendPaymentStatusChangeNotification;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class Transaction extends Model
{
    use Notifiable;

    protected $table = "transaction";

    public function student()
    {
        return $this->belongsTo('App\StudentDetail', 'student_id');
    }

    public function payer()
    {
        return $this->belongsTo('App\PayerDetail', 'payer_id');
    }

    public function merchant()
    {
        return $this->belongsTo('App\Models\Merchant', 'merchant_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo('App\Models\PaymentMethod', 'payment_method_id');
    }

    public function favourite()
    {
        return $this->hasOne('App\Models\Favourite');
    }


    public static function boot()
    {
        parent::boot();
        /**
         * Notification to the Student for any changes to the status of transaction
         *
         * @param Transaction $transaction
         *
         **/
        static::updated(function ($transaction) {
            $changes = $transaction->getDirty();

            if (array_key_exists('status', $changes)) {
                Log::info("Status has changed to " . $changes['status']);
                $transaction->notify(new SendPaymentStatusChangeNotification($transaction, Config::get('constants.link.transaction_status')));
            }


        });
    }

    /**
     * Route notifications for the mail channel.
     *
     * @param  \Illuminate\Notifications\Notification $notification
     * @return string
     */
    public function routeNotificationForMail($notification)
    {
        $user = User::whereHas('studentDetails', function ($q) {
            $q->where('id', $this->student_id);
        })->first();

        Log::info("Email address is $user->email");
        return $user->email;

    }

}
