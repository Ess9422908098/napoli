<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    // Well-known role slugs, referenced across the app instead of magic strings.
    public const ADMIN = 'admin';
    public const SALES = 'sales';
    public const STOREKEEPER = 'storekeeper';
    public const PRODUCTION = 'production';
    public const ACCOUNTANT = 'accountant';

    protected $fillable = ['slug', 'name', 'description'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function hasPermission(string $slug): bool
    {
        return $this->permissions()->where('slug', $slug)->exists();
    }
}
