<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskItem extends Model
{
    protected $table = 'task_items';

    protected $fillable = [
        'title',
        'owner',
        'description',
    ];
}
