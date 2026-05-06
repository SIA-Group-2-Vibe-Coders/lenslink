<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'cover_image',
        'is_public',
        'photographer_id',
        'client_id'
    ];

    public function photographer()
    {
        return $this->belongsTo(User::class, 'photographer_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function albums()
    {
        return $this->hasMany(Album::class);
    }
}
