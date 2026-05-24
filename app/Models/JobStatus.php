<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobStatus extends Model
{
    protected $fillable = [
        'type',
        'status',
        'message',
    ];

    public static function start(string $type, string $message): self
    {
        return self::create([
            'type' => $type,
            'status' => 'pending',
            'message' => $message,
        ]);
    }

    public function markCompleted(string $message): void
    {
        $this->update([
            'status' => 'completed',
            'message' => $message,
        ]);
    }

    public function markFailed(string $message): void
    {
        $this->update([
            'status' => 'failed',
            'message' => $message,
        ]);
    }
}
