<?php

namespace app\common\service\display;

use app\common\enum\DeviceEnum;
use app\common\model\sv\SvCityExposureRecord;
use app\common\model\sv\SvCityTouchRecord;
use app\common\model\sv\SvDeviceCircleLikeReplyRecord;
use app\common\model\sv\SvDeviceTakeOverRecord;
use app\common\model\sv\SvGroupBuyRecord;
use app\common\model\sv\SvLeadScrapingRecord;
use app\common\model\sv\SvPrivateMessage;
use app\common\service\FileService;

class IntentionCustomerService
{
    private const SOURCE_NAMES = [
        'lead_scraping' => '截流',
        'city_exposure' => '曝光',
        'city_touch' => '同城视频',
        'group_buy' => '团购',
        'sph_like' => '视频号点赞',
        'private_message' => '私信/评论',
    ];

    private static array $circleInteractionStatsCache = [];

    public static function parsePlatform(mixed $platform): ?int
    {
        $platform = strtolower(trim((string)$platform));
        if ($platform === '' || $platform === 'all') {
            return null;
        }

        $maps = [
            'shipinhao' => DeviceEnum::ACCOUNT_TYPE_SPH,
            'sph' => DeviceEnum::ACCOUNT_TYPE_SPH,
            'video' => DeviceEnum::ACCOUNT_TYPE_SPH,
            'xhs' => DeviceEnum::ACCOUNT_TYPE_XHS,
            'douyin' => DeviceEnum::ACCOUNT_TYPE_DY,
            'dy' => DeviceEnum::ACCOUNT_TYPE_DY,
            'kuaishou' => DeviceEnum::ACCOUNT_TYPE_KS,
            'ks' => DeviceEnum::ACCOUNT_TYPE_KS,
        ];

        if (isset($maps[$platform])) {
            return $maps[$platform];
        }

        return is_numeric($platform) ? (int)$platform : null;
    }

    public static function customers(int $userId, mixed $platform = null): array
    {
        $platform = self::parsePlatform($platform);
        $items = array_merge(
            self::leadScrapingItems($userId, $platform),
            self::cityExposureItems($userId, $platform),
            self::cityTouchItems($userId, $platform),
            self::groupBuyItems($userId, $platform),
            self::sphLikeItems($userId, $platform),
            self::privateMessageItems($userId, $platform)
        );

        $items = self::dedupeItems($items);
        usort($items, function (array $a, array $b) {
            return ($b['_timestamp'] ?? 0) <=> ($a['_timestamp'] ?? 0);
        });

        return $items;
    }

    public static function customerCounts(array $customers): array
    {
        $publicCount = 0;
        $privateCount = 0;
        foreach ($customers as $customer) {
            if (($customer['domain'] ?? 'public') === 'private') {
                $privateCount++;
            } else {
                $publicCount++;
            }
        }

        return [
            'total' => $publicCount + $privateCount,
            'public' => $publicCount,
            'private' => $privateCount,
        ];
    }

    public static function summarySourceStats(int $userId, mixed $platform = null): array
    {
        $platform = self::parsePlatform($platform);
        $douyinNearbyItems = array_merge(
            self::leadScrapingItems($userId, $platform, [
                'account_type' => DeviceEnum::ACCOUNT_TYPE_DY,
                'industry_type' => 1,
                'join_setting' => true,
            ]),
            self::cityExposureItems($userId, $platform, DeviceEnum::ACCOUNT_TYPE_DY),
            self::cityTouchItems($userId, $platform, DeviceEnum::ACCOUNT_TYPE_DY),
            self::groupBuyItems($userId, $platform, DeviceEnum::ACCOUNT_TYPE_DY)
        );
        $xhsPeerItems = self::leadScrapingItems($userId, $platform, [
            'account_type' => DeviceEnum::ACCOUNT_TYPE_XHS,
            'join_setting' => true,
        ]);
        $groupBuyItems = self::groupBuyItems($userId, $platform);

        return [
            [
                'key' => 'douyin_nearby',
                'name' => '抖音附近的客户',
                'count' => count(self::dedupeItems($douyinNearbyItems)),
                'unit' => '人',
            ],
            [
                'key' => 'xhs_peer',
                'name' => '小红书同行的客户',
                'count' => count(self::dedupeItems($xhsPeerItems)),
                'unit' => '人',
            ],
            [
                'key' => 'group_buy',
                'name' => '团购的客户',
                'count' => count(self::dedupeItems($groupBuyItems)),
                'unit' => '人',
            ],
        ];
    }

    public static function responseItem(array $item, bool $includeSourceInteraction = false): array
    {
        $stats = self::circleInteractionStats((int)($item['_user_id'] ?? 0), [
            $item['customer_name'] ?? '',
            $item['account_name'] ?? '',
        ]);

        if ($includeSourceInteraction) {
            $likeCount = (int)($item['_source_like_count'] ?? 0) + $stats['like_count'];
            $commentCount = (int)($item['_source_comment_count'] ?? 0) + $stats['comment_count'];
            $item['circle_like_count'] = $likeCount;
            $item['circle_comment_count'] = $commentCount;
            $item['circle_interaction_count'] = $likeCount + $commentCount;
        } else {
            $item['circle_interaction_count'] = max((int)($item['circle_interaction_count'] ?? 0), $stats['interaction_count']);
            $item['circle_like_count'] = max((int)($item['circle_like_count'] ?? 0), $stats['like_count']);
            $item['circle_comment_count'] = max((int)($item['circle_comment_count'] ?? 0), $stats['comment_count']);
        }

        unset(
            $item['_timestamp'],
            $item['_dedupe_keys'],
            $item['_is_private'],
            $item['_contact_token'],
            $item['_user_id'],
            $item['_source_interaction_count'],
            $item['_source_like_count'],
            $item['_source_comment_count'],
            $item['_private_chat_source']
        );
        return $item;
    }

    private static function leadScrapingItems(int $userId, ?int $platform, array $filters = []): array
    {
        $settingJoinType = !empty($filters['join_setting']) ? 'INNER' : 'LEFT';
        $query = SvLeadScrapingRecord::alias('r')
            ->join('sv_lead_scraping_setting s', 's.id = r.scraping_id and s.user_id = r.user_id', $settingJoinType);
        $prefix = 'r.';

        $query->where($prefix . 'user_id', $userId)
            ->where($prefix . 'status', '<>', 4)
            ->where($prefix . 'account', '<>', '')
            ->field($prefix . 'id,' . $prefix . 'user_id,' . $prefix . 'account,' . $prefix . 'account_name,' . $prefix . 'account_type,' . $prefix . 'platform,' . $prefix . 'avatar,' . $prefix . 'content,' . $prefix . 'filter_keyword,' . $prefix . 'touch_content,' . $prefix . 'comment_content,' . $prefix . 'exec_time,' . $prefix . 'create_time,' . $prefix . 'task_type,s.task_type as setting_task_type');

        if (isset($filters['account_type'])) {
            $query->where($prefix . 'account_type', (int)$filters['account_type']);
        }
        if (isset($filters['industry_type'])) {
            $query->where('s.industry_type', (int)$filters['industry_type']);
        }
        self::applyPlatformFilter($query, $platform, [$prefix . 'account_type', $prefix . 'platform']);

        return array_map(function (array $item) {
            return self::formatTouchItem($item, 'lead_scraping');
        }, $query->select()->toArray());
    }

    private static function cityExposureItems(int $userId, ?int $platform, ?int $accountType = null): array
    {
        $query = SvCityExposureRecord::where('user_id', $userId)
            ->where('status', '<>', 4)
            ->where('account', '<>', '')
            ->field('id,user_id,account,account_name,account_type,platform,avatar,exec_time,create_time');
        if ($accountType !== null) {
            $query->where('account_type', $accountType);
        }
        self::applyPlatformFilter($query, $platform, ['account_type', 'platform']);

        return array_map(function (array $item) {
            return self::formatTouchItem($item, 'city_exposure');
        }, $query->select()->toArray());
    }

    private static function cityTouchItems(int $userId, ?int $platform, ?int $accountType = null): array
    {
        $query = SvCityTouchRecord::alias('r')
            ->join('sv_city_touch_task t', 't.id = r.city_touch_id and t.user_id = r.user_id', 'left')
            ->where('r.user_id', $userId)
            ->where('r.status', '<>', 4)
            ->where('r.account', '<>', '')
            ->field('r.id,r.user_id,r.account,r.account_name,r.account_type,r.platform,r.avatar,r.content,r.filter_keyword,r.touch_content,r.comment_content,r.exec_time,r.create_time,r.task_type,t.task_type as setting_task_type,t.marker_method,t.chat_type');
        if ($accountType !== null) {
            $query->where('r.account_type', $accountType);
        }
        self::applyPlatformFilter($query, $platform, ['r.account_type', 'r.platform']);

        return array_map(function (array $item) {
            return self::formatTouchItem($item, 'city_touch');
        }, $query->select()->toArray());
    }

    private static function groupBuyItems(int $userId, ?int $platform, ?int $accountType = null): array
    {
        $query = SvGroupBuyRecord::alias('r')
            ->join('sv_group_buy_task_account gta', 'gta.id = r.group_buy_account_id and gta.user_id = r.user_id', 'left')
            ->join('sv_group_buy_task gt', 'gt.id = r.group_buy_id and gt.user_id = r.user_id', 'left')
            ->where('r.user_id', $userId)
            ->where('r.status', '<>', 4)
            ->where('r.account', '<>', '')
            ->field('r.id,r.user_id,r.account,r.account_name,r.account_type,r.platform,r.avatar,r.content,r.filter_keyword,r.touch_content,r.comment_content,r.exec_time,r.create_time,r.task_type,gta.task_type as account_task_type,gt.task_type as setting_task_type');
        if ($accountType !== null) {
            $query->where('r.account_type', $accountType);
        }
        self::applyPlatformFilter($query, $platform, ['r.account_type', 'r.platform']);

        return array_map(function (array $item) {
            return self::formatTouchItem($item, 'group_buy');
        }, $query->select()->toArray());
    }

    private static function sphLikeItems(int $userId, ?int $platform): array
    {
        if ($platform !== null && $platform !== DeviceEnum::ACCOUNT_TYPE_SPH) {
            return [];
        }

        return array_map(function (array $item) {
            $timestamp = self::toTimestamp($item['create_time'] ?? 0);
            $account = trim((string)($item['user_account'] ?? ''));
            $customerName = trim((string)($item['user_nickname'] ?? ''));
            $contact = self::extractContactToken([$item['content'] ?? '']);
            $isPrivate = $contact !== '';

            return self::formatItem([
                'id' => 'sph_like:' . $item['id'],
                '_user_id' => (int)($item['user_id'] ?? 0),
                'source_key' => 'sph_like',
                'source_name' => self::SOURCE_NAMES['sph_like'],
                'source_text' => '通过【' . self::SOURCE_NAMES['sph_like'] . '】流入',
                'account_name' => $customerName,
                'customer_name' => $customerName !== '' ? $customerName : ($account !== '' ? $account : '未知客户'),
                'avatar' => self::completeFileUrl((string)($item['user_avatar'] ?? '')),
                'platform' => DeviceEnum::ACCOUNT_TYPE_SPH,
                'account' => $account,
                'wechat_no' => $contact,
                'wechat_status' => $isPrivate ? 'recognized' : 'unrecognized',
                'latest_intention' => (string)($item['content'] ?? ''),
                'create_time' => self::formatTime($item['create_time'] ?? ''),
                '_timestamp' => $timestamp,
                '_contact_token' => $contact,
                '_is_private' => $isPrivate,
            ]);
        }, SvDeviceTakeOverRecord::where('user_id', $userId)
            ->where('type', 3)
            ->where('user_account', '<>', '')
            ->whereNull('delete_time')
            ->field('id,user_id,user_account,user_nickname,user_avatar,content,create_time')
            ->select()
            ->toArray());
    }

    private static function privateMessageItems(int $userId, ?int $platform): array
    {
        $query = SvPrivateMessage::where('user_id', $userId)
            ->where('message_task_type', 'in', [1, 2])
            ->whereNull('delete_time')
            ->where(function ($query) {
                $query->where('friend_id', '<>', '')->whereOr('account', '<>', '');
            })
            ->field('id,user_id,account,type,friend_id,avatar,author_name,message_content,message_task_type,message_timer,new_message_count,is_reply,reply_content,reply_time,create_time');
        self::applyPlatformFilter($query, $platform, ['type']);

        $rows = $query->select()->toArray();
        $conversationGroups = self::privateMessageConversationGroups($rows);
        $privateChatCounts = self::privateChatCountMap($userId, $conversationGroups);

        return array_map(function (array $item) use ($privateChatCounts) {
            $platform = (int)($item['type'] ?? 0);
            $timestamp = self::toTimestamp($item['reply_time'] ?? '') ?: self::toTimestamp($item['create_time'] ?? 0);
            $replyTime = self::formatTime($item['reply_time'] ?? '');
            $sourceAccount = trim((string)($item['account'] ?? ''));
            $friendId = trim((string)($item['friend_id'] ?? ''));
            $account = $friendId !== '' ? $friendId : $sourceAccount;
            $contact = self::extractContactToken([
                $item['message_content'] ?? '',
                $item['reply_content'] ?? '',
            ]);
            $isReply = (int)($item['is_reply'] ?? 0) === 1 || $replyTime !== '';
            $isPrivate = $contact !== '' || $isReply;
            $privateChatCount = $privateChatCounts[self::privateChatCountKey($platform, $sourceAccount, $friendId)] ?? 0;
            $sourceName = (int)($item['message_task_type'] ?? 0) === 1 ? '评论' : '私信';

            return self::formatItem([
                'id' => 'private_message:' . $item['id'],
                '_user_id' => (int)($item['user_id'] ?? 0),
                'source_key' => 'private_message',
                'source_name' => self::SOURCE_NAMES['private_message'],
                'source_text' => '通过【' . $sourceName . '】流入',
                'account_name' => trim((string)($item['author_name'] ?? '')),
                'customer_name' => trim((string)($item['author_name'] ?? '')) ?: ($account !== '' ? $account : '未知客户'),
                'avatar' => self::completeFileUrl((string)($item['avatar'] ?? '')),
                'platform' => $platform,
                'account' => $account,
                'wechat_no' => $contact,
                'wechat_status' => $isPrivate ? ($isReply ? 'replied' : 'recognized') : 'unrecognized',
                'first_private_time' => ($replyTime ?: self::formatTime($item['create_time'] ?? '')),
                'platform_message_count' => max(1, (int)($item['new_message_count'] ?? 0)),
                'platform_reply_count' => $isReply ? 1 : 0,
                'private_chat_count' => $privateChatCount,
                'latest_intention' => (string)($item['message_content'] ?? ''),
                'create_time' => $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : self::formatTime($item['create_time'] ?? ''),
                '_timestamp' => $timestamp,
                '_contact_token' => $contact,
                '_is_private' => $isPrivate,
                '_private_chat_source' => [
                    'id' => 'private_message:' . $item['id'],
                    'type' => $platform,
                    'account' => $sourceAccount,
                    'friend_id' => $friendId,
                    'count' => $privateChatCount,
                ],
            ]);
        }, $rows);
    }

    private static function privateMessageConversationGroups(array $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $platform = (int)($row['type'] ?? 0);
            $sourceAccount = trim((string)($row['account'] ?? ''));
            $friendId = trim((string)($row['friend_id'] ?? ''));
            if ($platform <= 0 || ($sourceAccount === '' && $friendId === '')) {
                continue;
            }

            $groups[$platform][self::privateChatCountKey($platform, $sourceAccount, $friendId)] = [
                'account' => $sourceAccount,
                'friend_id' => $friendId,
            ];
        }

        return $groups;
    }

    private static function privateChatCountMap(int $userId, array $conversationGroups): array
    {
        $counts = [];
        if ($userId <= 0 || empty($conversationGroups)) {
            return $counts;
        }

        $query = SvPrivateMessage::where('user_id', $userId)
            ->whereNull('delete_time')
            ->where(function ($query) use ($conversationGroups) {
                $index = 0;
                foreach ($conversationGroups as $platform => $conversations) {
                    $accounts = [];
                    $friendIds = [];
                    foreach ($conversations as $conversation) {
                        $accounts[] = (string)($conversation['account'] ?? '');
                        $friendIds[] = (string)($conversation['friend_id'] ?? '');
                    }
                    $accounts = array_values(array_unique($accounts));
                    $friendIds = array_values(array_unique($friendIds));
                    if (empty($accounts) || empty($friendIds)) {
                        continue;
                    }

                    $method = $index === 0 ? 'where' : 'whereOr';
                    $query->{$method}(function ($query) use ($platform, $accounts, $friendIds) {
                        $query->where('type', (int)$platform)
                            ->where('account', 'in', $accounts)
                            ->where('friend_id', 'in', $friendIds);
                    });
                    $index++;
                }
                if ($index === 0) {
                    $query->where('id', 0);
                }
            })
            ->field('type,account,friend_id,message_content,reply_content');

        $conversationKeys = [];
        foreach ($conversationGroups as $platform => $conversations) {
            foreach ($conversations as $conversation) {
                $key = self::privateChatCountKey(
                    (int)$platform,
                    (string)($conversation['account'] ?? ''),
                    (string)($conversation['friend_id'] ?? '')
                );
                $conversationKeys[$key] = true;
            }
        }

        foreach ($query->select()->toArray() as $row) {
            $key = self::privateChatCountKey(
                (int)($row['type'] ?? 0),
                (string)($row['account'] ?? ''),
                (string)($row['friend_id'] ?? '')
            );
            if (isset($conversationKeys[$key])) {
                $counts[$key] = ($counts[$key] ?? 0) + self::privateMessageDetailMessageCount($row);
            }
        }

        return $counts;
    }

    private static function privateMessageDetailMessageCount(array $row): int
    {
        $count = 0;
        if (self::privateMessageContentText($row['message_content'] ?? '') !== '') {
            $count++;
        }
        if (self::privateMessageContentText($row['reply_content'] ?? '') !== '') {
            $count++;
        }

        return $count;
    }

    private static function privateMessageContentText(mixed $content): string
    {
        $content = trim((string)$content);
        if ($content === '') {
            return '';
        }

        $parts = preg_split('/\s*&&\s*/', $content, 2);
        return trim((string)($parts[0] ?? ''));
    }

    private static function privateChatCountKey(int $platform, string $account, string $friendId): string
    {
        return $platform . ':' . strtolower(trim($account)) . ':' . strtolower(trim($friendId));
    }

    private static function formatTouchItem(array $item, string $sourceKey): array
    {
        $platform = self::resolvePlatform($item);
        $interactionStats = self::sourceInteractionStats($item, $sourceKey);
        $timestamp = self::toTimestamp($item['exec_time'] ?? ($item['create_time'] ?? 0));
        $customerName = trim((string)($item['account_name'] ?? ''));
        $account = trim((string)($item['account'] ?? ''));
        $latestIntention = self::firstText([
            $item['content'] ?? '',
            $item['comment_content'] ?? '',
            $item['touch_content'] ?? '',
            $item['filter_keyword'] ?? '',
        ]);
        $contact = self::extractContactToken([
            $item['content'] ?? '',
            $item['comment_content'] ?? '',
            $item['touch_content'] ?? '',
            $item['filter_keyword'] ?? '',
        ]);
        $isPrivate = $contact !== '';

        return self::formatItem([
            'id' => $sourceKey . ':' . $item['id'],
            '_user_id' => (int)($item['user_id'] ?? 0),
            'source_key' => $sourceKey,
            'source_name' => self::SOURCE_NAMES[$sourceKey] ?? '',
            'source_text' => '通过【' . (self::SOURCE_NAMES[$sourceKey] ?? '') . '】流入',
            'account_name' => $customerName,
            'customer_name' => $customerName !== '' ? $customerName : ($account !== '' ? $account : '未知客户'),
            'avatar' => self::completeFileUrl((string)($item['avatar'] ?? '')),
            'platform' => $platform,
            'account' => $account,
            'wechat_no' => $contact,
            'wechat_status' => $isPrivate ? 'recognized' : 'unrecognized',
            'first_private_time' => self::formatTime($item['create_time'] ?? ''),
            'platform_message_count' => 1,
            'platform_reply_count' => 0,
            'latest_intention' => $latestIntention,
            'create_time' => $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : self::formatTime($item['create_time'] ?? ''),
            '_timestamp' => $timestamp,
            '_contact_token' => $contact,
            '_is_private' => $isPrivate,
            '_source_interaction_count' => $interactionStats['interaction_count'],
            '_source_like_count' => $interactionStats['like_count'],
            '_source_comment_count' => $interactionStats['comment_count'],
        ]);
    }

    private static function formatItem(array $item): array
    {
        $platform = (int)($item['platform'] ?? 0);
        $contact = trim((string)($item['_contact_token'] ?? ''));
        $isPrivate = !empty($item['_is_private']);
        $item['account_name'] = (string)($item['account_name'] ?? ($item['customer_name'] ?? ''));
        $item['domain'] = $isPrivate ? 'private' : 'public';
        $item['platform'] = $platform;
        $item['platform_desc'] = $platform > 0 ? DeviceEnum::getAccountTypeDesc($platform) : '';
        $item['wechat_no'] = trim((string)($item['wechat_no'] ?? $contact));
        $item['first_private_time'] = (string)($item['first_private_time'] ?? '');
        $item['platform_message_count'] = (int)($item['platform_message_count'] ?? 0);
        $item['platform_reply_count'] = (int)($item['platform_reply_count'] ?? 0);
        $item['private_chat_count'] = (int)($item['private_chat_count'] ?? 0);
        $item['circle_interaction_count'] = (int)($item['circle_interaction_count'] ?? 0);
        $item['circle_like_count'] = (int)($item['circle_like_count'] ?? 0);
        $item['circle_comment_count'] = (int)($item['circle_comment_count'] ?? 0);
        $item['_source_interaction_count'] = (int)($item['_source_interaction_count'] ?? 0);
        $item['_source_like_count'] = (int)($item['_source_like_count'] ?? 0);
        $item['_source_comment_count'] = (int)($item['_source_comment_count'] ?? 0);
        $item['group_name'] = (string)($item['group_name'] ?? '');
        $item['_dedupe_keys'] = self::dedupeKeys($item);

        return $item;
    }

    private static function dedupeItems(array $items): array
    {
        $groups = [];
        $keyIndex = [];

        foreach ($items as $item) {
            $keys = array_values(array_unique(array_filter($item['_dedupe_keys'] ?? [])));
            if (empty($keys)) {
                $keys = [$item['id']];
            }

            $matchedIndexes = [];
            foreach ($keys as $key) {
                if (isset($keyIndex[$key])) {
                    $matchedIndexes[] = $keyIndex[$key];
                }
            }
            $matchedIndexes = array_values(array_unique($matchedIndexes));

            if (empty($matchedIndexes)) {
                $groups[] = $item;
                $groupIndex = array_key_last($groups);
            } else {
                sort($matchedIndexes);
                $groupIndex = $matchedIndexes[0];
                $groups[$groupIndex] = self::mergeItem($groups[$groupIndex], $item);

                for ($i = count($matchedIndexes) - 1; $i >= 1; $i--) {
                    $mergeIndex = $matchedIndexes[$i];
                    $groups[$groupIndex] = self::mergeItem($groups[$groupIndex], $groups[$mergeIndex]);
                    unset($groups[$mergeIndex]);
                }

                $groups = array_values($groups);
                $keyIndex = self::rebuildKeyIndex($groups);
                $groupIndex = $keyIndex[$keys[0]] ?? $groupIndex;
            }

            foreach ($keys as $key) {
                $keyIndex[$key] = $groupIndex;
            }
        }

        return array_values($groups);
    }

    private static function mergeItem(array $old, array $new): array
    {
        $oldPrivate = !empty($old['_is_private']);
        $newPrivate = !empty($new['_is_private']);
        if ($newPrivate && !$oldPrivate) {
            $base = $new;
            $other = $old;
        } elseif ($oldPrivate === $newPrivate && (($new['_timestamp'] ?? 0) > ($old['_timestamp'] ?? 0))) {
            $base = $new;
            $other = $old;
        } else {
            $base = $old;
            $other = $new;
        }

        $base['_is_private'] = $oldPrivate || $newPrivate;
        $base['domain'] = $base['_is_private'] ? 'private' : 'public';
        $base['_dedupe_keys'] = array_values(array_unique(array_merge($old['_dedupe_keys'] ?? [], $new['_dedupe_keys'] ?? [])));
        $base['_contact_token'] = self::firstText([$old['_contact_token'] ?? '', $new['_contact_token'] ?? '']);
        $base['wechat_no'] = $base['wechat_no'] ?: ($other['wechat_no'] ?? '') ?: ($base['_contact_token'] ?? '');
        $wechatStatus = (string)($base['wechat_status'] ?? '');
        $base['wechat_status'] = $base['_is_private'] && ($wechatStatus === '' || $wechatStatus === 'unrecognized')
            ? 'recognized'
            : ($wechatStatus ?: 'unrecognized');
        $base['first_private_time'] = self::firstText([$base['first_private_time'] ?? '', $other['first_private_time'] ?? '']);
        $base['latest_intention'] = self::firstText([$base['latest_intention'] ?? '', $other['latest_intention'] ?? '']);
        $base['platform_message_count'] = max((int)($old['platform_message_count'] ?? 0), (int)($new['platform_message_count'] ?? 0));
        $base['platform_reply_count'] = max((int)($old['platform_reply_count'] ?? 0), (int)($new['platform_reply_count'] ?? 0));
        $privateChatSource = self::selectPrivateChatSource($old, $new, $base);
        $base['private_chat_count'] = $privateChatSource !== []
            ? (int)$privateChatSource['count']
            : max((int)($old['private_chat_count'] ?? 0), (int)($new['private_chat_count'] ?? 0));
        if ($privateChatSource !== []) {
            $base['_private_chat_source'] = $privateChatSource;
            $base['id'] = $privateChatSource['id'];
            $base['platform'] = (int)$privateChatSource['type'];
            $base['platform_desc'] = $base['platform'] > 0 ? DeviceEnum::getAccountTypeDesc($base['platform']) : '';
            $base['account'] = $privateChatSource['friend_id'] !== ''
                ? $privateChatSource['friend_id']
                : $privateChatSource['account'];
        }
        $base['circle_interaction_count'] = max((int)($old['circle_interaction_count'] ?? 0), (int)($new['circle_interaction_count'] ?? 0));
        $base['circle_like_count'] = max((int)($old['circle_like_count'] ?? 0), (int)($new['circle_like_count'] ?? 0));
        $base['circle_comment_count'] = max((int)($old['circle_comment_count'] ?? 0), (int)($new['circle_comment_count'] ?? 0));
        $base['_source_interaction_count'] = (int)($old['_source_interaction_count'] ?? 0) + (int)($new['_source_interaction_count'] ?? 0);
        $base['_source_like_count'] = (int)($old['_source_like_count'] ?? 0) + (int)($new['_source_like_count'] ?? 0);
        $base['_source_comment_count'] = (int)($old['_source_comment_count'] ?? 0) + (int)($new['_source_comment_count'] ?? 0);
        $base['_user_id'] = (int)($base['_user_id'] ?? ($other['_user_id'] ?? 0));

        return $base;
    }

    private static function selectPrivateChatSource(array $old, array $new, array $base): array
    {
        $oldSource = self::normalizePrivateChatSource($old);
        $newSource = self::normalizePrivateChatSource($new);

        if ($oldSource === []) {
            return $newSource;
        }
        if ($newSource === []) {
            return $oldSource;
        }

        if ((int)$newSource['count'] > (int)$oldSource['count']) {
            return $newSource;
        }
        if ((int)$oldSource['count'] > (int)$newSource['count']) {
            return $oldSource;
        }

        return ($newSource['id'] === (string)($base['id'] ?? '')) ? $newSource : $oldSource;
    }

    private static function normalizePrivateChatSource(array $item): array
    {
        $source = $item['_private_chat_source'] ?? [];
        if (!is_array($source) || empty($source['id'])) {
            if (($item['source_key'] ?? '') !== 'private_message' || (int)($item['private_chat_count'] ?? 0) <= 0) {
                return [];
            }

            $source = [
                'id' => (string)($item['id'] ?? ''),
                'type' => (int)($item['platform'] ?? 0),
                'account' => '',
                'friend_id' => (string)($item['account'] ?? ''),
                'count' => (int)($item['private_chat_count'] ?? 0),
            ];
        }

        $count = (int)($source['count'] ?? 0);
        if ($count <= 0) {
            return [];
        }

        return [
            'id' => (string)($source['id'] ?? ''),
            'type' => (int)($source['type'] ?? 0),
            'account' => trim((string)($source['account'] ?? '')),
            'friend_id' => trim((string)($source['friend_id'] ?? '')),
            'count' => $count,
        ];
    }

    private static function rebuildKeyIndex(array $groups): array
    {
        $keyIndex = [];
        foreach ($groups as $index => $group) {
            foreach ($group['_dedupe_keys'] ?? [] as $key) {
                $keyIndex[$key] = $index;
            }
        }

        return $keyIndex;
    }

    private static function dedupeKeys(array $item): array
    {
        $platform = (int)($item['platform'] ?? 0);
        $account = trim((string)($item['account'] ?? ''));
        $customerName = trim((string)($item['customer_name'] ?? ''));
        $contact = trim((string)($item['_contact_token'] ?? $item['wechat_no'] ?? ''));
        $keys = [];

        if ($contact !== '') {
            $keys[] = 'contact:' . strtolower($contact);
        }
        if ($platform > 0 && $account !== '') {
            $keys[] = 'platform:' . $platform . ':account:' . strtolower($account);
        }
        if ($platform > 0 && $customerName !== '') {
            $keys[] = 'platform:' . $platform . ':name:' . strtolower($customerName);
        }
        $keys[] = 'source:' . ($item['id'] ?? uniqid('', true));

        return $keys;
    }

    private static function applyPlatformFilter($query, ?int $platform, array $fields): void
    {
        if ($platform === null) {
            return;
        }

        $query->where(function ($query) use ($fields, $platform) {
            foreach ($fields as $index => $field) {
                if ($index === 0) {
                    $query->where($field, $platform);
                } else {
                    $query->whereOr($field, $platform);
                }
            }
        });
    }

    private static function resolvePlatform(array $item): int
    {
        $accountType = (int)($item['account_type'] ?? 0);
        if ($accountType > 0) {
            return $accountType;
        }

        return (int)($item['platform'] ?? 0);
    }

    public static function sourceInteractionStats(array $item, string $sourceKey): array
    {
        $likeCount = 0;
        $commentCount = 0;

        if ($sourceKey === 'lead_scraping') {
            $actionType = self::firstActionType($item, ['task_type', 'setting_task_type']);
            if ($actionType === DeviceEnum::AUTO_TASK_SCENE_COMMENT_COMMENT) {
                $commentCount = 1;
            } elseif ($actionType === DeviceEnum::AUTO_TASK_SCENE_MARK_CLUE) {
                $likeCount = 1;
            }
        } elseif ($sourceKey === 'city_touch') {
            if ((int)($item['chat_type'] ?? 1) === 1) {
                $actionType = self::firstActionType($item, ['task_type', 'setting_task_type']);
                if ($actionType === DeviceEnum::AUTO_TASK_SCENE_COMMENT_COMMENT) {
                    $commentCount = 1;
                } elseif ($actionType !== DeviceEnum::AUTO_TASK_SCENE_COMMENT_MSG && in_array(1, self::normalizeIntList($item['marker_method'] ?? []), true)) {
                    $likeCount = 1;
                }
            }
        } elseif ($sourceKey === 'group_buy') {
            $actionType = self::firstActionType($item, ['task_type', 'account_task_type', 'setting_task_type']);
            if ($actionType === DeviceEnum::AUTO_TASK_SCENE_COMMENT_COMMENT) {
                $commentCount = 1;
            } elseif ($actionType === DeviceEnum::AUTO_TASK_SCENE_MARK_CLUE) {
                $likeCount = 1;
            }
        }

        return [
            'interaction_count' => $likeCount + $commentCount,
            'like_count' => $likeCount,
            'comment_count' => $commentCount,
        ];
    }

    private static function firstActionType(array $item, array $fields): int
    {
        foreach ($fields as $field) {
            $taskType = (int)($item[$field] ?? 0);
            if (in_array($taskType, [
                DeviceEnum::AUTO_TASK_SCENE_COMMENT_COMMENT,
                DeviceEnum::AUTO_TASK_SCENE_COMMENT_MSG,
                DeviceEnum::AUTO_TASK_SCENE_MARK_CLUE,
            ], true)) {
                return $taskType;
            }
        }

        return 0;
    }

    private static function normalizeIntList(mixed $value): array
    {
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return [];
            }
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : preg_split('/[\s,;|\x{ff0c}\x{ff1b}]+/u', $value);
        } elseif (!is_array($value)) {
            $value = [$value];
        }

        $items = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                foreach (self::normalizeIntList($item) as $nestedItem) {
                    $items[$nestedItem] = true;
                }
                continue;
            }

            $item = trim((string)$item);
            if (is_numeric($item)) {
                $items[(int)$item] = true;
            }
        }

        return array_keys($items);
    }

    private static function extractContactToken(array $texts): string
    {
        $text = implode(' ', array_map(static fn($value) => (string)$value, $texts));
        if ($text === '') {
            return '';
        }

        if (preg_match('/1[3-9]\d{9}/', $text, $matches)) {
            return $matches[0];
        }
        if (preg_match('/(?:微信|wx|v信|VX|WeChat|wechat)[:：\s]*([a-zA-Z][-_a-zA-Z0-9]{5,19})/u', $text, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private static function firstText(array $values): string
    {
        foreach ($values as $value) {
            $value = trim((string)$value);
            if ($value !== '' && strtolower($value) !== 'null') {
                return $value;
            }
        }

        return '';
    }

    private static function circleInteractionStats(int $userId, array $nicknames): array
    {
        $result = [
            'interaction_count' => 0,
            'like_count' => 0,
            'comment_count' => 0,
        ];
        $nicknames = array_values(array_unique(array_filter(array_map(static function (mixed $value): string {
            $value = trim((string)$value);
            if ($value === '' || strtolower($value) === 'null' || $value === '未知客户') {
                return '';
            }

            return $value;
        }, $nicknames))));

        if ($userId <= 0 || empty($nicknames)) {
            return $result;
        }

        sort($nicknames);
        $cacheKey = $userId . ':' . md5((string)json_encode($nicknames, JSON_UNESCAPED_UNICODE));
        if (isset(self::$circleInteractionStatsCache[$cacheKey])) {
            return self::$circleInteractionStatsCache[$cacheKey];
        }

        $records = SvDeviceCircleLikeReplyRecord::where('user_id', $userId)
            ->whereNull('delete_time')
            ->where('nickname', 'in', $nicknames)
            ->field('type')
            ->select()
            ->toArray();

        foreach ($records as $record) {
            $type = (int)($record['type'] ?? 0);
            $result['interaction_count']++;
            if (in_array($type, [1, 3], true)) {
                $result['like_count']++;
            }
            if (in_array($type, [2, 3], true)) {
                $result['comment_count']++;
            }
        }

        self::$circleInteractionStatsCache[$cacheKey] = $result;
        return $result;
    }

    private static function completeFileUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return FileService::getFileUrl(ltrim($url, '/'));
    }

    private static function formatTime(mixed $value): string
    {
        if (empty($value) || strtolower((string)$value) === 'null') {
            return '';
        }

        if (is_numeric($value)) {
            $timestamp = (int)$value;
            return $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : '';
        }

        return (string)$value;
    }

    private static function toTimestamp(mixed $value): int
    {
        if (empty($value) || strtolower((string)$value) === 'null') {
            return 0;
        }
        if (is_numeric($value)) {
            return (int)$value;
        }

        $timestamp = strtotime((string)$value);
        return false === $timestamp ? 0 : $timestamp;
    }
}
