<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RedirectsAfterSave;

abstract class Controller
{
    use RedirectsAfterSave;
}
