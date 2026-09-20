<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemRequest extends Model
{
    //
    protected $fillable = ['user_id', 'equipment_id', 'quantity', 'status', 'requested_date', 'remarks', 'decision_reason', 'decided_at', 'decided_by'];

    protected function casts(): array
    {
        return [
            'requested_date' => 'date',
            'decided_at' => 'datetime',
        ];
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** The admin who approved or declined this. */
    public function decider()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'Pending';
    }

    /**
     * Whether the request can be filled from what is on the shelf right now.
     * The approve button is disabled on a false, and ItemRequestController
     * re-checks under a row lock before deducting anything.
     */
    public function canBeFilled(): bool
    {
        $equipment = $this->equipment;

        return $equipment !== null
            && ! $equipment->isRetired()
            && $equipment->available_quantity >= $this->quantity;
    }

    /** "Declined Sep 19 by Quincy Jane O." — the audit line in the decided list. */
    public function decisionLine(): string
    {
        if ($this->isPending()) {
            return 'Awaiting review';
        }

        $when = $this->decided_at ? $this->decided_at->format('M j') : $this->updated_at?->format('M j');
        $who = $this->decider?->name;

        return trim($this->status.($when ? ' '.$when : '').($who ? ' by '.$who : ''));
    }
}
