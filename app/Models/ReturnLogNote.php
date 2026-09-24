<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A correction appended to a return log.
 *
 * Return logs are immutable: this is the only way a record gets amended, and it
 * adds rather than overwrites, so the original reading and every correction to
 * it are both still there with their authors and times.
 */
class ReturnLogNote extends Model
{
    protected $fillable = ['return_log_id', 'user_id', 'body'];

    public function returnLog()
    {
        return $this->belongsTo(ReturnLog::class);
    }

    /** Nullable: a note outlives the account that wrote it. */
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
