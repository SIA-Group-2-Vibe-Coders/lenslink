<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'action',
        'file_size_bytes',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
