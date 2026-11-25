<?php

namespace Iquesters\Organisation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A role holder of an organisation (President, Secretary…), shown on its profile. */
class OrganisationOfficeBearer extends Model
{
    protected $table = 'organisation_office_bearers';

    protected $fillable = [
        'organisation_id',
        'title',
        'name',
        'email',
        'phone',
        'is_public',
        'sort',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }
}
