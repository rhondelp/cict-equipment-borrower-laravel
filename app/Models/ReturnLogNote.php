<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnLogNote extends Model
{
    protected $fillable = ['return_log_id', 'user_id', 'body'];

    public function returnLog()
    {
        return $this->belongsTo(ReturnLog::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
