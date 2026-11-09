<?php namespace EvolutionCMS\Support;

/**
 * Keeps a manager without `manage_groups` from changing user group membership beyond its own reach.
 *
 * Setting a user's groups is otherwise unguarded (`save_user` alone reaches it), so a limited
 * manager could add itself or anyone to any user group and inherit that group's document access.
 */
class UserGroupGuard
{
    /**
     * The group ids to store for a user after an edit, honouring the actor's reach.
     *
     * With `manage_groups` the posted set stands. Without it the actor may only add or remove
     * groups it belongs to: a group the target already carries that the actor is not in is kept,
     * and a posted group the actor is not in is ignored. So a manager can neither grant a group it
     * does not hold nor strip a group it cannot see.
     *
     * @param int[] $actorGroups  groups the acting manager belongs to
     * @param int[] $current      the target user's current groups
     * @param int[] $posted       the groups requested by the form
     * @param bool  $manageGroups the actor holds `manage_groups`
     * @return int[]
     */
    public static function authorizedGroups(array $actorGroups, array $current, array $posted, bool $manageGroups): array
    {
        $posted = array_map('intval', $posted);
        if ($manageGroups) {
            return array_values(array_unique($posted));
        }

        $actor = array_map('intval', $actorGroups);
        $current = array_map('intval', $current);

        $kept = array_diff($current, $actor);       // groups beyond the actor's reach stay untouched
        $set = array_intersect($posted, $actor);    // only the actor's own groups may be set

        return array_values(array_unique(array_merge($kept, $set)));
    }
}
