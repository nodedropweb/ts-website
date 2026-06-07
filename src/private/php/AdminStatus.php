<?php

namespace Wruczek\TSWebsite;

use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TeamSpeak3Exception as TeamSpeak3_Exception;
use Wruczek\PhpFileCache\PhpFileCache;
use Wruczek\TSWebsite\Utils\TeamSpeakUtils;

/**
 * Class used to generate an array with statuses of admins, used
 * later to power the "Admin status" feature in the sidebar
 */
class AdminStatus {

    use Utils\SingletonTait;

    private $cache;

    public const STATUS_STYLE_GROUPED = 1;
    public const STATUS_STYLE_GROUPED_HIDE_EMPTY_GROUPS = 2;
    public const STATUS_STYLE_LIST = 3;
    public const STATUS_STYLE_LIST_ONLINE_FIRST = 4;

    public function __construct() {
        $this->cache = new PhpFileCache(__CACHE_DIR, "adminstatus");
    }

    public function clearCache(): void {
        $this->cache->clearCache();
    }

    public function getCachedAdminClients(array $adminGroups): ?array {
        if (empty($adminGroups)) {
            return [];
        }

        $cacheKey = "adminstatus_" . md5(json_encode($adminGroups));

        return $this->cache->refreshIfExpired($cacheKey, function () use ($adminGroups) {
            if(TeamSpeakUtils::i()->checkTSConnection()) {
                $nodeServer = TeamSpeakUtils::i()->getTSNodeServer();
                $clients = [];

                foreach ($adminGroups as $groupId) {
                    try {
                        $clients[$groupId] = $nodeServer->serverGroupClientList($groupId);
                    } catch (TeamSpeak3_Exception $e) {
                        TeamSpeakUtils::i()->addExceptionToExceptionsList($e);
                        $clients[$groupId] = [];
                    }
                }

                return $clients;
            }
            return null;
        }, Config::get("cache_adminstatus"));
    }

    public function getStatus(array $adminGroups, $format, bool $hideOffline = false, array $ignoredUsersDbids = []) {
        if ($format !== self::STATUS_STYLE_GROUPED
            && $format !== self::STATUS_STYLE_GROUPED_HIDE_EMPTY_GROUPS
            && $format !== self::STATUS_STYLE_LIST
            && $format !== self::STATUS_STYLE_LIST_ONLINE_FIRST) {
            throw new \InvalidArgumentException("Invalid format specified");
        }

        // Cache the whole getStatus result for a short time (same as clientlist)
        $cacheKey = "status_full_" . md5(json_encode(func_get_args()));
        return $this->cache->refreshIfExpired($cacheKey, function() use ($adminGroups, $format, $hideOffline, $ignoredUsersDbids) {
            $serverGroupList = CacheManager::i()->getServerGroupList();
            $adminStatus = $this->getCachedAdminClients($adminGroups);
            $data = [];
            $seenCldbids = []; // Used for deduplication in list modes

            if ($serverGroupList === null || $adminStatus === null) {
                // if we dont have a server group list or the
                // cached admin clients, we cannot do anything
                // (its probably a connection issue)
                // false means "data problem"
                return false;
            }

            foreach ($adminGroups as $adminGroupId) {
                // try to get that group from server group list
                $serverGroup = @$serverGroupList[$adminGroupId];

                // skip if we cant get that group
                if ($serverGroup === null) {
                    continue;
                }

                $groupClients = [];
                $cachedClients = $adminStatus[$adminGroupId] ?? [];

                if (!is_array($cachedClients)) {
                    $cachedClients = [];
                }

                foreach ($cachedClients as $client) {
                    $cldbid = (int) $client["cldbid"];

                    if (in_array($cldbid, $ignoredUsersDbids)) {
                        continue;
                    }

                    // Deduplicate for list modes
                    if (($format === self::STATUS_STYLE_LIST || $format === self::STATUS_STYLE_LIST_ONLINE_FIRST)) {
                        if (isset($seenCldbids[$cldbid])) {
                            continue;
                        }
                        $seenCldbids[$cldbid] = true;
                    }

                    $onlineClient = CacheManager::i()->getClient($cldbid);

                    if ($format === self::STATUS_STYLE_LIST_ONLINE_FIRST) {
                        // in list style, inside of data we have
                        // 2 additional arrays: online and offline
                        // we add online users to online, and offline users to offline
                        // at the end, we combine both arrays
                        $data[$onlineClient ? "online" : "offline"][] = [
                            "client" => $onlineClient ?: $client,
                            "group" => $serverGroup
                        ];
                    } else {
                        // when dealing with other formats...
                        if ($onlineClient !== null) {
                            // if online, add everything from the $onlineClient
                            $groupClients[] = $onlineClient;
                        } else if (!$hideOffline) {
                            // if offline, we only have info from $client returned by the server group list
                            $groupClients[] = $client;
                        }
                    }
                }

                // sort clients, always show online first
                if ($format !== self::STATUS_STYLE_LIST_ONLINE_FIRST) {
                    uasort($groupClients, function ($a, $b) {
                        if (isset($a["clid"], $b["clid"])) {
                            return 0;
                        }

                        return isset($a["clid"]) ? -1 : 1;
                    });

                    // add sorted data to our results
                    $data[$adminGroupId] = $serverGroup + ["clients" => $groupClients];
                }
            }

            // in the online first format...
            if ($format === self::STATUS_STYLE_LIST_ONLINE_FIRST) {
                if ($hideOffline) {
                    // we dont care about the offline users if hideOffline is true
                    $data = $data["online"] ?? [];
                } else {
                    // ...combine online and offline arrays
                    // see line #89 for explanation
                    // online users go before offline users
                    // NOTE: we are using array_merge instead of the "+"
                    // operator, because we have default numeric keys.
                    // Using "+" would make us loose some data
                    $data = array_merge($data["online"] ?? [], $data["offline"] ?? []);
                }
            }

            return ["format" => $format, "data" => $data];
        }, Config::get("cache_clientlist", 15));
    }

}
