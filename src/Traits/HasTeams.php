<?php

namespace Iquesters\Organisation\Traits;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Iquesters\Organisation\Models\Team;

trait HasTeams
{
    public function teams(): MorphToMany
    {
        return $this->morphToMany(
            Team::class,
            'model',
            'model_has_teams'
        );
    }

    public function assignTeam($team): self
    {
        if (is_string($team)) {
            $team = Team::where('uid', $team)->firstOrFail();
        }

        if (is_int($team)) {
            $team = Team::findOrFail($team);
        }

        $this->teams()->syncWithoutDetaching([$team->id]);

        return $this;
    }

    public function removeTeam($team): self
    {
        if (is_string($team)) {
            $team = Team::where('uid', $team)->firstOrFail();
        }

        if (is_int($team)) {
            $team = Team::findOrFail($team);
        }

        $this->teams()->detach($team->id);

        return $this;
    }

    public function hasTeam($team): bool
    {
        if (is_string($team)) {
            return $this->teams()->where('uid', $team)->exists();
        }

        if (is_int($team)) {
            return $this->teams()->where('id', $team)->exists();
        }

        if ($team instanceof Team) {
            return $this->teams()->where('id', $team->id)->exists();
        }

        return false;
    }

    public function syncTeams(array $teams): self
    {
        $teamIds = [];
        
        foreach ($teams as $team) {
            if (is_string($team)) {
                $org = Team::where('uid', $team)->firstOrFail();
                $teamIds[] = $org->id;
            } elseif (is_int($team)) {
                $teamIds[] = $team;
            } elseif ($team instanceof Team) {
                $teamIds[] = $team->id;
            }
        }

        $this->teams()->sync($teamIds);

        return $this;
    }
}