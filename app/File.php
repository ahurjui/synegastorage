<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class File extends Model
{
    const STATUS_ARCHIVED = 2;
    const STATUS_ACTIVE = 1;
    //

    protected $appends = ['downloadUrl'];

    public function getDownloadUrlAttribute()
    {
        return URL::to('/').'/api/files/download/'.$this->id.'/'.$this->name;
    }
}

