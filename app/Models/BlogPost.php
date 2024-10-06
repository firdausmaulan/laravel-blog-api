<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'content', 'image', 'user_id'];

    // Each blog post belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
