<?php

namespace EvolutionCMS\Services\DocumentSave;

use Closure;
use EvolutionCMS\Legacy\Permissions;
use EvolutionCMS\Models\MemberGroup;

/**
 * Everything the save needs from the manager session and the CMS, as plain values and
 * closures, so the service can run outside the manager (tests, CLI, an AJAX lane).
 * @since 3.5.9
 */
final class DocumentSaveContext
{
    /** @var int[]|null */
    private ?array $userGroups = null;

    /**
     * @param int[] $managerDocgroups document groups of the session ($_SESSION['mgrDocgroups'])
     * @param Closure(string):mixed $config
     * @param Closure(string):bool $permission
     * @param Closure(string, array):void $event
     * @param Closure(string):string $stripAlias
     * @param Closure(string):int $toTimestamp
     * @param Closure(int):array $parentIds
     * @param Closure(int):bool $canCreateIn udperms check for a parent
     * @param Closure(int):bool $canEdit udperms check for the document being edited
     * @param Closure():array $userGroupsLoader document groups the user is a member of, read fresh
     * @param Closure(string):string $lang
     */
    public function __construct(
        public readonly int $userId,
        public readonly int $role,
        public readonly array $managerDocgroups,
        public readonly int $now,
        private readonly Closure $config,
        private readonly Closure $permission,
        private readonly Closure $event,
        private readonly Closure $stripAlias,
        private readonly Closure $toTimestamp,
        private readonly Closure $parentIds,
        private readonly Closure $canCreateIn,
        private readonly Closure $canEdit,
        private readonly Closure $userGroupsLoader,
        private readonly Closure $lang,
    ) {
    }

    public static function fromManager(): self
    {
        $evo = evo();
        $userId = (int) $evo->getLoginUserID('mgr');

        return new self(
            userId: $userId,
            role: (int) ($_SESSION['mgrRole'] ?? 0),
            managerDocgroups: array_map('intval', (array) ($_SESSION['mgrDocgroups'] ?? [])),
            now: $evo->timestamp((int) get_by_key($_SERVER, 'REQUEST_TIME', 0)),
            config: fn (string $key) => $evo->getConfig($key),
            permission: fn (string $name) => (bool) $evo->hasPermission($name),
            event: function (string $name, array $params) use ($evo) {
                $evo->invokeEvent($name, $params);
            },
            stripAlias: fn (string $alias) => (string) $evo->stripAlias($alias),
            toTimestamp: fn (string $date) => (int) $evo->toTimeStamp($date),
            parentIds: fn (int $id) => (array) $evo->getParentIds($id),
            canCreateIn: fn (int $parent) => Permissions::canCreateIn($parent),
            // udperms asks the same of a document as of a parent: can this user reach it
            canEdit: fn (int $id) => Permissions::canCreateIn($id),
            userGroupsLoader: fn () => array_values(array_unique(array_map('intval', MemberGroup::query()
                ->join('membergroup_access', 'membergroup_access.membergroup', '=', 'member_groups.user_group')
                ->where('member_groups.member', $userId)
                ->pluck('documentgroup')->all()))),
            lang: fn (string $key) => (string) __('global.' . $key),
        );
    }

    public function isAdministrator(): bool
    {
        return $this->role === 1;
    }

    public function config(string $key): mixed
    {
        return ($this->config)($key);
    }

    public function can(string $permission): bool
    {
        return ($this->permission)($permission);
    }

    public function fire(string $event, array $params): void
    {
        ($this->event)($event, $params);
    }

    public function stripAlias(string $alias): string
    {
        return ($this->stripAlias)($alias);
    }

    public function toTimestamp(string $date): int
    {
        return ($this->toTimestamp)($date);
    }

    /**
     * @return int[]
     */
    public function parentIds(int $id): array
    {
        return ($this->parentIds)($id);
    }

    public function canCreateIn(int $parent): bool
    {
        return ($this->canCreateIn)($parent);
    }

    public function canEdit(int $id): bool
    {
        return $id > 0 && ($this->canEdit)($id);
    }

    /**
     * Document groups the user belongs to, queried once per save.
     *
     * @return int[]
     */
    public function userGroups(): array
    {
        return $this->userGroups ??= ($this->userGroupsLoader)();
    }

    public function lang(string $key): string
    {
        return ($this->lang)($key);
    }
}
