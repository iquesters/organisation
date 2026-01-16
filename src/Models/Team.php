<?php

namespace Iquesters\Organisation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use App\Models\User;

class Team extends Model
{
    use HasFactory;

    protected $table = 'teams';

    protected $fillable = [
        'uid',
        'name',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'uid' => 'string',
    ];

    public function metas(): HasMany
    {
        return $this->hasMany(TeamMeta::class, 'ref_parent');
    }

    // public function models(string $modelClass): MorphToMany
    // {
    //     return $this->morphedByMany($modelClass, 'model', 'model_has_teams');
    // }

    public function organisations(): MorphToMany
    {
        return $this->morphedByMany(
            Organisation::class,
            'model',
            'model_has_teams'
        );
    }

    // Teams ↔ Users
    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            User::class,
            'model',
            'model_has_teams'
        );
    }

    public function getMetaValue(string $key)
    {
        $meta = $this->metas()->where('meta_key', $key)->first();
        return $meta ? $meta->meta_value : null;
    }

    public function setMetaValue(string $key, $value)
    {
        return $this->metas()->updateOrCreate(
            ['meta_key' => $key],
            ['meta_value' => $value]
        );
    }
}