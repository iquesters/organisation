<?php

namespace Iquesters\Organisation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModelHasTeam extends Model
{
    protected $table = 'model_has_teams';

    protected $fillable = [
        'team_id',
        'model_type',
        'model_id',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}