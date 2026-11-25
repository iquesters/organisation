<?php

namespace Iquesters\Organisation\Support;

use Illuminate\Support\Facades\Route;
use Iquesters\Organisation\Models\Organisation;

/**
 * An organisation's own Terms and Privacy policy (profile fields terms_url /
 * terms_text, privacy_url / privacy_text, policies_version). For each: its
 * link, else its page with the text (/organisations/{uid}/terms|privacy),
 * else the platform's page. What members agree to when they apply.
 */
class OrganisationPolicies
{
    public const KINDS = [
        'terms' => ['label' => 'Terms and conditions', 'platform_route' => 'ui.terms'],
        'privacy' => ['label' => 'Privacy policy', 'platform_route' => 'ui.privacy'],
    ];

    /** @return array<int, array{label: string, url: string}> */
    public static function links(Organisation $organisation): array
    {
        $links = [];
        foreach (self::KINDS as $kind => $meta) {
            if ($url = self::url($organisation, $kind)) {
                $links[] = ['label' => $organisation->name.': '.$meta['label'], 'url' => $url];
            }
        }

        return $links;
    }

    public static function url(Organisation $organisation, string $kind): ?string
    {
        $own = trim((string) $organisation->getMetaValue($kind.'_url'));
        if ($own !== '') {
            return $own;
        }
        if (self::text($organisation, $kind) !== '') {
            return route('organisations.policy', ['organisationUid' => $organisation->uid, 'kind' => $kind], absolute: false);
        }
        $platform = self::KINDS[$kind]['platform_route'];

        return Route::has($platform) ? route($platform, absolute: false) : null;
    }

    public static function text(Organisation $organisation, string $kind): string
    {
        return trim((string) $organisation->getMetaValue($kind.'_text'));
    }

    /** The organisation's version, else "platform" (the platform's pages apply). */
    public static function version(Organisation $organisation): string
    {
        return trim((string) $organisation->getMetaValue('policies_version')) ?: 'platform';
    }
}
