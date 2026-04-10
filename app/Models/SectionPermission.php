<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionPermission extends Model
{
    public const MODULE_DASHBOARD = 'dashboard';

    public const MODULE_EVENTS = 'events';

    public const MODULE_MEMBERS = 'members';

    public const MODULE_LEADERS = 'leaders';

    public const MODULE_PODS = 'pods';

    public const MODULE_INFO_NOTES = 'info_notes';

    public const MODULE_TASK_ITEMS = 'task_items';

    public const MODULE_YEAR_THEME = 'year_theme';

    public const MODULE_TIPPER_TOPPER = 'tipper_topper';

    public const MODULE_FINANCE = 'financien';

    public const MODULE_CAMP_BUDGETS = 'camp_budgets';

    public const MODULE_CAMP_PLAYBOOKS = 'camp_playbooks';

    public const MODULE_PROFILE = 'profile';

    public const ALL_MODULES = [
        self::MODULE_DASHBOARD,
        self::MODULE_EVENTS,
        self::MODULE_MEMBERS,
        self::MODULE_LEADERS,
        self::MODULE_PODS,
        self::MODULE_INFO_NOTES,
        self::MODULE_TASK_ITEMS,
        self::MODULE_YEAR_THEME,
        self::MODULE_TIPPER_TOPPER,
        self::MODULE_FINANCE,
        self::MODULE_CAMP_BUDGETS,
        self::MODULE_CAMP_PLAYBOOKS,
        self::MODULE_PROFILE,
    ];

    protected $fillable = [
        'section',
        'role',
        'module',
        'can_view',
        'can_create',
        'can_update',
        'can_delete',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_create' => 'boolean',
        'can_update' => 'boolean',
        'can_delete' => 'boolean',
    ];
}
