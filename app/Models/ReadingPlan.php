<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingPlan extends Model
{
    const STATUS_UNREAD = 'unread';
    const STATUS_READING = 'reading';
    const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'user_id',
        'book_id',
        'target_date',
        'completed_at',
        'status',
    ];

    protected $casts = [
        'target_date' => 'date',
        'completed_at' => 'date',
        'status' => \App\Enums\ReadingPlanStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
