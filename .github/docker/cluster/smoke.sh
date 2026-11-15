#!/bin/bash
# Checks the running cluster of docker-compose.yml through its balancer: that
# requests really alternate between the nodes, and that whatever one node does
# to a session, an upload or the cache is what the other node sees.
set -euo pipefail

here=$(cd "$(dirname "$0")" && pwd)
# shellcheck source=../ci/lib.sh
. "$here/../ci/lib.sh"

DOCKER=${DOCKER:-docker}
COMPOSE=("$DOCKER" compose -f "${COMPOSE_FILE:-$here/docker-compose.yml}")
BASE=${EVO_CLUSTER_URL:-http://127.0.0.1:${CLUSTER_PORT:-8080}}
ADMIN=${EVO_ADMIN:-admin}
PASSWORD=${EVO_ADMIN_PASSWORD:-Passw0rd123}
SEEDED='Install Successful!'

work=$(mktemp -d)
trap 'rm -rf "$work"' EXIT
JAR=$work/cookies
probe="cluster-$(date +%s)-$$"

node_ip() {
    "$DOCKER" inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' "$("${COMPOSE[@]}" ps -q "$1")"
}

# Runs a shell command on a service, as www-data on the nodes: that is who
# writes the shared files in production.
on() {
    local service=$1
    shift
    # </dev/null: exec -T would otherwise read the stdin of a `while read` loop.
    if [ "$service" = php1 ] || [ "$service" = php2 ]; then
        "${COMPOSE[@]}" exec -T -u www-data -w /var/www/html "$service" sh -c "$1" < /dev/null
    else
        "${COMPOSE[@]}" exec -T "$service" sh -c "$1" < /dev/null
    fi
}

IP1=$(node_ip php1)
IP2=$(node_ip php2)
echo "php1=$IP1 php2=$IP2 balancer=$BASE"

# fetch PATH [curl args...]: the body lands in $work/body, the status in
# STATUS and the node that answered, read from the balancer's header, in NODE.
fetch() {
    local path=$1 upstream
    shift
    STATUS=$(curl -sS -o "$work/body" -D "$work/head" -w '%{http_code}' \
        -b "$JAR" -c "$JAR" -H 'Accept-Language: en' -e "$BASE/manager/" "$@" "$BASE$path")
    upstream=$(tr -d '\r' < "$work/head" | sed -n 's/^[Xx]-[Ee]vo-[Uu]pstream: *//p' | tail -1)
    case "${upstream%:*}" in
        "$IP1") NODE=php1 ;;
        "$IP2") NODE=php2 ;;
        *) NODE="unknown($upstream)" ;;
    esac
    echo "  ${STATUS} ${NODE} ${path}"
}

body_has() {
    grep -qF -- "$1" "$work/body"
}

# The nodes of the last requests, oldest first, one per line.
SEEN=$work/nodes
seen_reset() {
    : > "$SEEN"
}
seen() {
    echo "$NODE" >> "$SEEN"
}
# Both nodes answered, and never the same one twice in a row: round robin.
assert_alternated() {
    [ "$(sort -u "$SEEN" | tr '\n' ' ')" = "php1 php2 " ] || fail "$1: not both nodes answered ($(tr '\n' ' ' < "$SEEN"))"
    [ -z "$(uniq -d "$SEEN")" ] || fail "$1: a node answered twice in a row ($(tr '\n' ' ' < "$SEEN"))"
}

say "The balancer alternates between the nodes"
seen_reset
for _ in 1 2 3 4; do
    fetch /
    [ "$STATUS" = 200 ] || fail "the front page answered $STATUS on $NODE"
    body_has "$SEEDED" || fail "$NODE did not render the seeded document"
    seen
done
assert_alternated "front page"

say "Shared paths are the same folders on both nodes, writable by www-data"
mounts() {
    "$DOCKER" inspect -f '{{range .Mounts}}{{.Destination}}{{"\n"}}{{end}}' "$("${COMPOSE[@]}" ps -q "$1")" \
        | grep '^/var/www/html/' | sort
}
mounts php1 > "$work/mounts1"
mounts php2 > "$work/mounts2"
[ -s "$work/mounts1" ] || fail "php1 mounts no shared path"
diff "$work/mounts1" "$work/mounts2" || fail "the nodes mount different shared paths"
while read -r dir; do
    on php1 "echo $probe > '$dir/.$probe'"
    [ "$(on php2 "cat '$dir/.$probe'")" = "$probe" ] || fail "$dir is not shared"
    on php2 "rm '$dir/.$probe'"
    echo "  shared: $dir"
done < "$work/mounts1"
# Compiled views stay per node: Blade writes them in place, and a node reading
# a view the other is halfway through writing would run half a template.
for dir in /var/www/html/core/storage/blade /tmp; do
    on php1 "echo $probe > '$dir/.$probe'"
    if on php2 "test -e '$dir/.$probe'"; then
        fail "$dir is shared but is meant to stay per node"
    fi
    on php1 "rm '$dir/.$probe'"
    echo "  per node: $dir"
done

say "A manager session started on one node is valid on the other"
fetch /manager/
body_has 'id="loginfrm"' || fail "the manager did not show its login form"
fetch '/manager/?a=0' -H "Origin: $BASE" -d ajax=1 -d rememberme=0 \
    --data-urlencode "username=$ADMIN" --data-urlencode "password=$PASSWORD"
head -c 9 "$work/body" | grep -qi '^location:' || {
    head -c 500 "$work/body" >&2
    fail "the login was refused on $NODE"
}
login_node=$NODE
seen_reset
for _ in 1 2 3 4; do
    fetch /manager/
    [ "$STATUS" = 200 ] || fail "the manager answered $STATUS on $NODE"
    body_has 'name="csrf-token"' && ! body_has 'id="loginfrm"' \
        || fail "$NODE did not know the session started on $login_node"
    seen
done
assert_alternated "manager"
csrf=$(sed -n 's/.*name="csrf-token" content="\([^"]*\)".*/\1/p' "$work/body" | head -1)
[ -n "$csrf" ] || fail "no CSRF token in the manager page"

keys=$(on redis "redis-cli --user evo --pass secret --no-auth-warning -n 2 dbsize" | tr -d '\r')
echo "  redis session keys: $keys"
[ "${keys:-0}" -gt 0 ] || fail "no session reached Redis"
mode=$(on php1 'printenv EVO_SESSION || echo 1')
for node in php1 php2; do
    # Only what the node wrote since it started: a session stored while the
    # release was installed is in the image, on both nodes alike.
    since=$("$DOCKER" inspect -f '{{.State.StartedAt}}' "$("${COMPOSE[@]}" ps -q "$node")")
    # Laravel's file store is the session that counts in mode 1; in mode 0 it is
    # PHP's own session, whose files would be in session.save_path.
    files=$(on "$node" "find core/storage/sessions -maxdepth 1 -type f ! -name .gitignore -newermt '$since' | wc -l")
    # Empty ones are left by CLI runs (artisan, the scheduler), which start a
    # session that holds nothing; a web session on disk has content.
    if [ "$mode" = 0 ]; then
        files=$((files + $(on "$node" 'd=$(php -r "echo session_save_path() ?: sys_get_temp_dir();"); find "${d##*;}" -maxdepth 1 -name "sess_*" -size +0 | wc -l')))
    fi
    [ "$files" -eq 0 ] || fail "$node stored $files session file(s) on its own disk (EVO_SESSION=$mode)"
done
echo "  no session files on either node (EVO_SESSION=$mode)"

say "A file manager token issued on one node is accepted on the other"
fetch '/manager/index.php?a=31'
token_node=$NODE
token=$(sed -n 's/.*name="token" value="\([^"]*\)".*/\1/p' "$work/body" | head -1)
[ -n "$token" ] || fail "the file manager page carries no upload token"
# A 1x1 transparent GIF: an image extension every default install accepts.
printf 'GIF89a\001\000\001\000\200\000\000\000\000\000\377\377\377!\371\004\001\000\000\000\000,\000\000\000\000\001\000\001\000\000\002\002D\001\000;' > "$work/$probe.gif"
fetch /manager/index.php -F a=31 -F path=assets/images -F "_token=$csrf" -F "token=$token" \
    -F "userfile[]=@$work/$probe.gif;type=image/gif"
[ "$NODE" != "$token_node" ] || fail "the upload went to $NODE, where its token came from"
[ "$STATUS" = 200 ] || fail "the upload answered $STATUS on $NODE"
body_has 'upload-ok' || {
    grep -oE '<p class="upload-[a-z]+">.*</p>|<span class="warning">[^<]*' "$work/body" | head -5 >&2 || true
    fail "$NODE refused the upload with the token issued by $token_node"
}
seen_reset
for _ in 1 2; do
    fetch "/assets/images/$probe.gif"
    [ "$STATUS" = 200 ] && cmp -s "$work/body" "$work/$probe.gif" || fail "$NODE does not serve the file uploaded on the other node"
    seen
done
assert_alternated "uploaded file"

say "A cache purge on one node reaches the other"
marker="Cluster marker $probe"
on pgsql "psql -U evo -d evolution -v ON_ERROR_STOP=1 -qc \"UPDATE evo_site_content SET content = content || '<p>$marker</p>' WHERE id = 1\""
# The page cache still holds the old document on both nodes: what follows is
# a purge reaching them, not a cache that was never used.
seen_reset
for _ in 1 2; do
    fetch /
    body_has "$SEEDED" && ! body_has "$marker" || fail "$NODE did not serve the cached page"
    seen
done
assert_alternated "cached front page"
fetch "/manager/index.php?a=26&_token=$csrf"
[ "$STATUS" = 200 ] || fail "refreshing the site answered $STATUS on $NODE"
purge_node=$NODE
seen_reset
for _ in 1 2; do
    fetch /
    body_has "$marker" || fail "$NODE still serves the page purged on $purge_node"
    seen
done
assert_alternated "purged front page"

say "A logout on one node ends the session on the other"
fetch "/manager/index.php?a=8&_token=$csrf"
logout_node=$NODE
seen_reset
for _ in 1 2; do
    fetch /manager/
    body_has 'id="loginfrm"' || fail "$NODE still holds the session logged out on $logout_node"
    seen
done
assert_alternated "manager after logout"

say "Only php1 runs the scheduler"
"${COMPOSE[@]}" logs php1 2>&1 | grep -q 'starting php artisan schedule:work' || fail "php1 never started the scheduler"
if "${COMPOSE[@]}" logs php1 2>&1 | grep -q 'schedule:work exited'; then
    fail "the scheduler on php1 exited"
fi
if "${COMPOSE[@]}" logs php2 2>&1 | grep -q 'schedule:work'; then
    fail "php2 runs a scheduler too"
fi
echo "  php1 schedules, php2 does not"

say "OK: two nodes, one session store, one database, shared files"
