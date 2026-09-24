<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    //
    protected $fillable = ['user_id', 'borrow_transaction_id', 'message', 'notification_type', 'send_date'];

    protected function casts(): array
    {
        return ['send_date' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** The loan this was about, where it was about one. */
    public function borrowTransaction()
    {
        return $this->belongsTo(BorrowTransaction::class);
    }
}
