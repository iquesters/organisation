<?php

namespace Iquesters\Organisation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Iquesters\Foundation\Support\UserModel;

/** A user's role in one organisation: member or org-admin. */
class OrganisationUserRole extends Model
{
    public const ROLE_MEMBER = 'member';

    public const ROLE_ORG_ADMIN = 'org-admin';

    public const ROLES = [self::ROLE_MEMBER, self::ROLE_ORG_ADMIN];

    protected $table = 'organisation_user_roles';

    protected $fillable = ['uid', 'organisation_id', 'user_id', 'user_type', 'role', 'status', 'created_by', 'updated_by'];

    protected static function booted(): void
    {
        static::creating(function (self $row) {
            $row->uid ??= (string) Str::ulid();
            $row->status ??= 'active';
        });
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::name(), 'user_id');
    }

    public static function label(string $role): string
    {
        return $role === self::ROLE_ORG_ADMIN ? 'Organisation admin' : 'Member';
    }
}
