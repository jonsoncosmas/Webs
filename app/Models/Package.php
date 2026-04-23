<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    public const BASIC = 'basic';

    public const PRO = 'pro';

    public const ELITE = 'elite';

    protected $fillable = [
        'slug',
        'name',
        'features',
        'max_users',
        'has_bus_tracking',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'has_bus_tracking' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }

    /**
     * @return array<int, array{slug:string,name:string,features:array<string,bool>,max_users:?int,has_bus_tracking:bool}>
     */
    public static function catalog(): array
    {
        return [
            [
                'slug' => self::BASIC,
                'name' => 'Basic',
                'features' => ['department_tracking' => true, 'ai_basic' => true],
                'max_users' => 300,
                'has_bus_tracking' => false,
            ],
            [
                'slug' => self::PRO,
                'name' => 'Pro',
                'features' => ['department_tracking' => true, 'ai_basic' => true, 'ai_advanced' => true],
                'max_users' => 1500,
                'has_bus_tracking' => false,
            ],
            [
                'slug' => self::ELITE,
                'name' => 'Elite',
                'features' => ['department_tracking' => true, 'ai_basic' => true, 'ai_advanced' => true, 'ai_premium' => true, 'bus_tracking' => true],
                'max_users' => null,
                'has_bus_tracking' => true,
            ],
        ];
    }
}
