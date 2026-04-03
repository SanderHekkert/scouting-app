<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskCategory extends Model
{
    public const DEFAULT_NAMES = [
        'Veiligheid',
        'Financiën',
        'Opkomsten',
        'Algemeen',
        'Ouder Contact',
        'Kampen',
        'Creatief',
        'Vloot',
    ];

    protected $fillable = [
        'name',
        'position',
    ];
}
