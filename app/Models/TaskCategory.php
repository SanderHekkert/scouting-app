<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSection;
use Illuminate\Database\Eloquent\Model;

class TaskCategory extends Model
{
    use BelongsToSection;

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
        'section',
        'name',
        'position',
    ];
}
