<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSocials extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'provider_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const PROVIDER_GOOGLE = 'google';
    public const PROVIDER_FACEBOOK = 'facebook';

    // relations
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    // =========

    // static functions
    public static function fGetProviders(): array
    {
        return [
            self::PROVIDER_GOOGLE => 'Google',
            self::PROVIDER_FACEBOOK => 'Facebook',
        ];
    }
    // ================
}
