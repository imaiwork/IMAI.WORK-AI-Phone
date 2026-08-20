<?php

namespace app\api\lists\display;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsExtendInterface;
use app\common\service\display\IntentionCustomerService;

/**
 * 意向客户列表
 */
class IntentionCustomerLists extends BaseApiDataLists implements ListsExtendInterface
{
    private string $domain = 'public';
    private ?int $platform = null;
    private string $sourceKey = '';
    private string $wechatNo = '';
    private string $wechatStatus = '';
    private ?array $customersCache = null;
    private ?array $filteredCustomersCache = null;
    private array $domainItemsCache = [];

    public function __construct()
    {
        parent::__construct();

        $domain = (string)$this->request->get('domain', 'public');
        $this->domain = $domain === 'private' ? 'private' : 'public';
        $this->platform = IntentionCustomerService::parsePlatform($this->request->get('platform', 'all'));
        $this->sourceKey = trim((string)$this->request->get('source_key', ''));
        $this->wechatNo = trim((string)$this->request->get('wechat_no', ''));
        $this->wechatStatus = trim((string)$this->request->get('wechat_status', ''));
    }

    public function lists(): array
    {
        $items = $this->getDomainItems($this->domain);
        $items = array_slice($items, $this->limitOffset, $this->limitLength);
        $includeSourceInteraction = $this->domain === 'public';

        return array_map(static function (array $item) use ($includeSourceInteraction) {
            return IntentionCustomerService::responseItem($item, $includeSourceInteraction);
        }, $items);
    }

    public function count(): int
    {
        return count($this->getDomainItems($this->domain));
    }

    public function extend(): array
    {
        $counts = IntentionCustomerService::customerCounts($this->getFilteredCustomers());

        return [
            'domain' => $this->domain,
            'public_customer_count' => $counts['public'],
            'private_customer_count' => $counts['private'],
            'source_stats' => IntentionCustomerService::summarySourceStats($this->getCurrentUserId(), $this->platform),
        ];
    }

    private function getDomainItems(string $domain): array
    {
        if (isset($this->domainItemsCache[$domain])) {
            return $this->domainItemsCache[$domain];
        }

        $items = array_values(array_filter($this->getFilteredCustomers(), function (array $item) use ($domain) {
            return ($item['domain'] ?? 'public') === $domain;
        }));

        return $this->domainItemsCache[$domain] = $items;
    }

    private function getCustomers(): array
    {
        if ($this->customersCache !== null) {
            return $this->customersCache;
        }

        return $this->customersCache = IntentionCustomerService::customers($this->getCurrentUserId(), $this->platform);
    }

    private function getFilteredCustomers(): array
    {
        if ($this->filteredCustomersCache !== null) {
            return $this->filteredCustomersCache;
        }

        return $this->filteredCustomersCache = array_values(array_filter($this->getCustomers(), function (array $item) {
            if ($this->sourceKey !== '' && (string)($item['source_key'] ?? '') !== $this->sourceKey) {
                return false;
            }
            if ($this->wechatNo !== '' && stripos((string)($item['wechat_no'] ?? ''), $this->wechatNo) === false) {
                return false;
            }
            if ($this->wechatStatus !== '' && (string)($item['wechat_status'] ?? '') !== $this->wechatStatus) {
                return false;
            }

            return true;
        }));
    }

    private function getCurrentUserId(): int
    {
        if ($this->userId > 0) {
            return $this->userId;
        }

        return (int)(request()->userId ?? request()->userInfo['user_id'] ?? 0);
    }
}
