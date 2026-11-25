<?php

namespace Iquesters\Organisation\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Iquesters\Organisation\Models\Organisation;

/**
 * An organisation's profile: details kept in organisation_metas, a logo on the
 * private disk, and a public page (/o/{slug}) the admins can switch on.
 */
class OrganisationProfile
{
    /** Private disk folder for logos (served through a route, no storage:link). */
    public const LOGO_DIRECTORY = 'organisation/logos';

    public const LOGO_DISK = 'local';

    /** meta_key: whether the public page is on ("1"). */
    public const PUBLIC_PAGE = 'public_page';

    /** meta_key: the logo's path on the private disk. */
    public const LOGO = 'logo_path';

    /**
     * The profile fields: id (meta_key), label, type, group, whether the public
     * page shows it, and validation rules.
     */
    public static function fields(): array
    {
        return [
            ['id' => 'tagline', 'label' => 'Tagline', 'type' => 'text', 'group' => 'about', 'public' => true, 'rules' => ['nullable', 'string', 'max:150']],
            ['id' => 'about', 'label' => 'About', 'type' => 'textarea', 'group' => 'about', 'public' => true, 'rules' => ['nullable', 'string', 'max:5000']],
            ['id' => 'founded_year', 'label' => 'Founded (year)', 'type' => 'number', 'group' => 'about', 'public' => true, 'rules' => ['nullable', 'integer', 'min:1800', 'max:2100']],
            ['id' => 'affiliation', 'label' => 'Affiliation / board', 'type' => 'text', 'group' => 'about', 'public' => true, 'rules' => ['nullable', 'string', 'max:255']],
            ['id' => 'registration_number', 'label' => 'Registration number', 'type' => 'text', 'group' => 'about', 'public' => true, 'rules' => ['nullable', 'string', 'max:100']],
            ['id' => 'email', 'label' => 'Email', 'type' => 'email', 'group' => 'contact', 'public' => true, 'rules' => ['nullable', 'email', 'max:255']],
            ['id' => 'phone', 'label' => 'Phone', 'type' => 'tel', 'group' => 'contact', 'public' => true, 'rules' => ['nullable', 'string', 'max:32']],
            ['id' => 'website', 'label' => 'Website', 'type' => 'url', 'group' => 'contact', 'public' => true, 'rules' => ['nullable', 'url:http,https', 'max:255']],
            ['id' => 'address', 'label' => 'Address', 'type' => 'textarea', 'group' => 'contact', 'public' => true, 'rules' => ['nullable', 'string', 'max:1000']],
            ['id' => 'facebook', 'label' => 'Facebook', 'type' => 'url', 'group' => 'social', 'public' => true, 'rules' => ['nullable', 'url:https', 'max:255']],
            ['id' => 'instagram', 'label' => 'Instagram', 'type' => 'url', 'group' => 'social', 'public' => true, 'rules' => ['nullable', 'url:https', 'max:255']],
            ['id' => 'linkedin', 'label' => 'LinkedIn', 'type' => 'url', 'group' => 'social', 'public' => true, 'rules' => ['nullable', 'url:https', 'max:255']],
            ['id' => 'x', 'label' => 'X (Twitter)', 'type' => 'url', 'group' => 'social', 'public' => true, 'rules' => ['nullable', 'url:https', 'max:255']],
            ['id' => 'youtube', 'label' => 'YouTube', 'type' => 'url', 'group' => 'social', 'public' => true, 'rules' => ['nullable', 'url:https', 'max:255']],
        ];
    }

    public static function groups(): array
    {
        return ['about' => 'About', 'contact' => 'Contact', 'social' => 'Social links'];
    }

    /** Form schema slug of the profile's edit form. */
    public const FORM = 'organisation-profile';

    /**
     * The edit form (user-interface form engine): sections About / Contact /
     * Social links / Office bearers (a repeater) / Logo and public page. Posts
     * JSON to PUT /organisations/{uid}/profile. organisation:seed rebuilds it.
     */
    public static function formSchema(): array
    {
        $descriptions = [
            'about' => 'Who you are, in a few lines.',
            'contact' => 'How people reach you.',
            'social' => 'Full addresses, starting with https://.',
        ];
        $fields = [];
        foreach (self::groups() as $group => $title) {
            $fields[] = ['id' => 'section_'.$group, 'type' => 'section', 'label' => $title, 'description' => $descriptions[$group]];
            foreach (self::fields() as $field) {
                if ($field['group'] !== $group) {
                    continue;
                }
                $fields[] = array_filter([
                    'id' => $field['id'],
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'rows' => $field['type'] === 'textarea' ? ($field['id'] === 'about' ? 5 : 3) : null,
                    'placeholder' => $field['type'] === 'url' ? 'https://' : null,
                ], fn ($v) => $v !== null);
            }
        }

        return [
            'defaultFieldSize' => 'auto',
            'submitButtonLabel' => 'Save profile',
            'modes' => [
                'edit' => ['endpoint' => '/organisations/{uid}/profile', 'method' => 'PUT', 'allowDelete' => false],
            ],
            'fields' => [
                ...$fields,
                ['id' => 'section_bearers', 'type' => 'section', 'label' => 'Office bearers', 'description' => 'In the order they should appear. Email and phone are shown to members only.'],
                [
                    'id' => 'office_bearers',
                    'label' => 'Office bearers',
                    'type' => 'repeater',
                    'addLabel' => 'Add office bearer',
                    'fields' => [
                        ['id' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true, 'maxLength' => 100, 'placeholder' => 'e.g. President'],
                        ['id' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'maxLength' => 255],
                        ['id' => 'email', 'label' => 'Email', 'type' => 'email', 'maxLength' => 255],
                        ['id' => 'phone', 'label' => 'Phone', 'type' => 'tel', 'maxLength' => 32],
                        ['id' => 'is_public', 'label' => 'On the public page', 'type' => 'checkbox'],
                    ],
                ],
                ['id' => 'section_page', 'type' => 'section', 'label' => 'Logo and public page', 'description' => 'The public page shows the details and the office bearers ticked above, never their contact details.'],
                ['id' => 'logo', 'label' => 'Logo', 'type' => 'file', 'accept' => '.jpg,.jpeg,.png,.webp', 'helperText' => 'JPG, PNG or WEBP, up to 2 MB.'],
                ['id' => 'remove_logo', 'label' => 'Remove the current logo', 'type' => 'checkbox'],
                ['id' => 'public_page', 'label' => 'Public page (anyone can see the profile)', 'type' => 'checkbox'],
            ],
        ];
    }

    public static function syncForm(): void
    {
        if (! Schema::hasTable('form_schemas')) {
            return;
        }

        $db = DB::table('form_schemas');
        $existing = (clone $db)->where('slug', self::FORM)->first(['uid', 'created_at']);
        DB::table('form_schemas')->updateOrInsert(['slug' => self::FORM], [
            'uid' => $existing->uid ?? (string) Str::ulid(),
            'name' => 'Organisation profile',
            'description' => 'Organisation profile edit form (organisation:seed rebuilds it)',
            'schema' => json_encode(self::formSchema(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => 'active',
            'created_at' => $existing->created_at ?? now(),
            'updated_at' => now(),
        ]);
    }

    public static function isPublic(Organisation $organisation): bool
    {
        return $organisation->status === 'active'
            && $organisation->slug
            && $organisation->getMetaValue(self::PUBLIC_PAGE) === '1';
    }

    /** Save the details (blank removes a value), the logo and the public page switch. */
    public static function save(Organisation $organisation, array $values, ?UploadedFile $logo, bool $removeLogo, bool $publicPage, int $userId): void
    {
        foreach (self::fields() as $field) {
            $value = trim((string) ($values[$field['id']] ?? ''));
            if ($value === '') {
                $organisation->metas()->where('meta_key', $field['id'])->delete();

                continue;
            }
            $organisation->metas()->updateOrCreate(
                ['meta_key' => $field['id']],
                ['meta_value' => $value, 'status' => 'active', 'updated_by' => $userId]
            );
        }

        $oldLogo = $organisation->getMetaValue(self::LOGO);
        if ($logo || $removeLogo) {
            if ($oldLogo) {
                Storage::disk(self::LOGO_DISK)->delete($oldLogo);
            }
            $organisation->metas()->where('meta_key', self::LOGO)->delete();
        }
        if ($logo) {
            $path = $logo->store(self::LOGO_DIRECTORY.'/'.$organisation->uid, self::LOGO_DISK);
            $organisation->metas()->create(['meta_key' => self::LOGO, 'meta_value' => $path, 'status' => 'active', 'created_by' => $userId, 'updated_by' => $userId]);
        }

        $organisation->metas()->updateOrCreate(
            ['meta_key' => self::PUBLIC_PAGE],
            ['meta_value' => $publicPage ? '1' : '0', 'status' => 'active', 'updated_by' => $userId]
        );

        if (! $organisation->slug) {
            $organisation->forceFill(['slug' => self::uniqueSlug($organisation)])->save();
        }
    }

    /** "TTVians Alumni" → "ttvians-alumni", or "ttvians-alumni-2" when taken. */
    public static function uniqueSlug(Organisation $organisation): string
    {
        $base = Str::limit(Str::slug($organisation->name) ?: 'organisation', 90, '');
        $slug = $base;
        for ($i = 2; Organisation::query()->where('slug', $slug)->whereKeyNot($organisation->id)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    /**
     * Replace the office bearers with the given rows (blank rows are skipped).
     *
     * @param  array<int, array{title?: string, name?: string, email?: string, phone?: string, is_public?: mixed}>  $rows
     */
    public static function saveOfficeBearers(Organisation $organisation, array $rows): void
    {
        $organisation->officeBearers()->delete();

        $sort = 0;
        foreach ($rows as $row) {
            $title = trim((string) ($row['title'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            if ($title === '' || $name === '') {
                continue;
            }
            $organisation->officeBearers()->create([
                'title' => Str::limit($title, 100, ''),
                'name' => Str::limit($name, 255, ''),
                'email' => trim((string) ($row['email'] ?? '')) ?: null,
                'phone' => trim((string) ($row['phone'] ?? '')) ?: null,
                'is_public' => ! empty($row['is_public']),
                'sort' => $sort += 10,
            ]);
        }
    }
}
