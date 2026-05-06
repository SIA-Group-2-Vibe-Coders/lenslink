<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_filename',
        'file_path',
        'thumbnail_path',
        'watermarked_path',
        'file_size',
        'mime_type',
        'status',
        'album_id',
        'photographer_id'
    ];

    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    public function photographer()
    {
        return $this->belongsTo(User::class, 'photographer_id');
    }
}
