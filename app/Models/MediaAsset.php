<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaAsset extends Model
{
    protected $fillable = [
        'disk', 'path', 'original_name', 'mime_type', 'size',
        'width', 'height', 'checksum', 'collection', 'uploaded_by',
    ];
}
