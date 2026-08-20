<?php

namespace app\api\logic\map;

use app\api\logic\ApiLogic;
use app\common\cache\ExportCache;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\ModelConfig;
use app\common\model\map\MapLeadConversation;
use app\common\model\map\MapLeadMessage;
use app\common\model\map\MapLeadRecord;
use app\common\model\user\User;
use app\common\service\ToolsService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\facade\Db;
use think\facade\Log;

class LeadLogic extends ApiLogic
{
    private const STATUS_PROCESSING = 0;
    private const STATUS_SUCCESS = 1;
    private const STATUS_FAIL = 2;

    private const ROLE_USER = 'user';
    private const ROLE_ASSISTANT = 'assistant';

    private const CONTENT_TEXT = 'text';
    private const CONTENT_CARDS = 'map_lead_cards';
    private const CONTENT_ERROR = 'error';

    private const BILLING_SCENE = 'map_chat_clues';
    private const DEFAULT_CARD_UNIT = 1.0;

    public static function chat(array $params): bool
    {
        try {
            $request = self::normalizeChatParams($params);
            Log::channel('map')->write('[地图获客][chat开始] 收到请求 | ' . json_encode([
                'uid'          => (int)self::$uid,
                'query'        => self::stringValue($request['query'] ?? ''),
                'city'         => self::stringValue($request['city'] ?? ''),
                'types'        => self::stringValue($request['types'] ?? ''),
                'biz'          => self::stringValue($request['biz'] ?? ''),
                'target_count' => (int)($request['target_count'] ?? 0),
                'page'         => (int)($request['page'] ?? 0),
                'page_size'    => (int)($request['page_size'] ?? 0),
            ], JSON_UNESCAPED_UNICODE));

            $billingUnit = self::billingUnit();
            self::checkBillingBalance($request, $billingUnit);
            [$conversation, $userMessage] = self::createConversationAndUserMessage($request);
            Log::channel('map')->write('[地图获客][会话创建] 会话与用户消息已创建 | ' . json_encode([
                'uid'             => (int)self::$uid,
                'conversation_id' => (string)$conversation->conversation_id,
                'user_message_id' => (int)$userMessage->id,
            ], JSON_UNESCAPED_UNICODE));

            $search = self::searchWithFallback($request);
            $response = $search['response'];
            if ((int)($response['code'] ?? 0) !== 10000) {
                $message = self::responseMessage($response);
                Log::channel('map')->warning('[地图获客][远端失败] 检索返回非成功码 | ' . json_encode([
                    'uid'             => (int)self::$uid,
                    'conversation_id' => (string)$conversation->conversation_id,
                    'code'            => (int)($response['code'] ?? 0),
                    'message'         => $message,
                ], JSON_UNESCAPED_UNICODE));
                $assistantMessage = self::createAssistantErrorMessage($conversation, $request, $message, $response);

                self::$returnData = [
                    'conversation_id'    => $conversation->conversation_id,
                    'user_message'       => self::formatMessage($userMessage),
                    'assistant_message'  => self::formatMessage($assistantMessage),
                ];
                return true;
            }

            $data = $search['data'];
            $parsed = $search['parsed'];
            $cards = $search['cards'];
            $assistantMessage = self::createAssistantCardsMessage($conversation, $request, $response, $data, $parsed, $cards, $billingUnit);

            self::saveRecords((string)$conversation->conversation_id, (int)$assistantMessage->id, $cards);

            Log::channel('map')->write('[地图获客][chat结束] 处理成功 | ' . json_encode([
                'uid'             => (int)self::$uid,
                'conversation_id' => (string)$conversation->conversation_id,
                'message_id'      => (int)$assistantMessage->id,
                'lead_count'      => count($cards),
                'fallback_used'   => (bool)($data['fallback_used'] ?? false),
            ], JSON_UNESCAPED_UNICODE));

            self::$returnData = [
                'conversation_id'    => $conversation->conversation_id,
                'user_message'       => self::formatMessage($userMessage),
                'assistant_message'  => self::formatMessage($assistantMessage, self::publicCards($cards)),
            ];
            return true;
        } catch (\Throwable $e) {
            Log::channel('map')->error('[地图获客][chat异常] 处理失败 | ' . json_encode([
                'uid'       => (int)self::$uid,
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'code'      => (int)$e->getCode(),
            ], JSON_UNESCAPED_UNICODE));
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function delete(array $params): bool
    {
        $conversationId = trim((string)($params['conversation_id'] ?? ''));
        if ($conversationId === '') {
            self::setError('会话ID不能为空');
            return false;
        }

        $conversation = MapLeadConversation::where('conversation_id', $conversationId)
            ->where('user_id', self::$uid)
            ->findOrEmpty();
        if ($conversation->isEmpty()) {
            self::setError('会话不存在或无权限访问');
            return false;
        }

        Db::startTrans();
        try {
            MapLeadRecord::where('conversation_id', $conversationId)
                ->where('user_id', self::$uid)
                ->delete();
            MapLeadMessage::where('conversation_id', $conversationId)
                ->where('user_id', self::$uid)
                ->delete();
            $conversation->delete();

            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function export(array $params): bool
    {
        try {
            $conversationId = trim((string)($params['conversation_id'] ?? ''));
            $messageId = (int)($params['message_id'] ?? 0);
            $message = null;

            if ($conversationId === '' && $messageId <= 0) {
                self::setError('会话ID或消息ID不能为空');
                return false;
            }

            if ($conversationId !== '') {
                $conversation = MapLeadConversation::where('conversation_id', $conversationId)
                    ->where('user_id', self::$uid)
                    ->findOrEmpty();
                if ($conversation->isEmpty()) {
                    self::setError('会话不存在或无权限访问');
                    return false;
                }
            } else {
                $message = MapLeadMessage::where('id', $messageId)
                    ->where('user_id', self::$uid)
                    ->findOrEmpty();
                if ($message->isEmpty()) {
                    self::setError('消息不存在或无权限访问');
                    return false;
                }
                if ((string)$message->content_type !== self::CONTENT_CARDS) {
                    self::setError('该消息无可导出的线索');
                    return false;
                }

                $conversationId = (string)$message->conversation_id;
                $conversation = MapLeadConversation::where('conversation_id', $conversationId)
                    ->where('user_id', self::$uid)
                    ->findOrEmpty();
            }

            $extra = [];
            if ($message && !$message->isEmpty()) {
                $extra = self::decodeJson($message->extra ?? '');
            } else {
                $latestCardsMessage = MapLeadMessage::where('conversation_id', $conversationId)
                    ->where('user_id', self::$uid)
                    ->where('content_type', self::CONTENT_CARDS)
                    ->order('id', 'desc')
                    ->findOrEmpty();
                if (!$latestCardsMessage->isEmpty()) {
                    $message = $latestCardsMessage;
                    $extra = self::decodeJson($latestCardsMessage->extra ?? '');
                }
            }
            $keyword = self::buildExportKeyword($conversation, $extra);

            $rows = MapLeadRecord::field('name,addr,phone,tag,rating,create_time')
                ->where('conversation_id', $conversationId)
                ->where('user_id', self::$uid)
                ->order('id', 'asc')
                ->select()
                ->toArray();

            if (empty($rows)) {
                $rows = self::collectConversationCardsExportRows($conversationId);
            }

            if (empty($rows)) {
                self::setError('没有可导出的线索数据');
                return false;
            }

            $exportRows = [];
            foreach ($rows as $row) {
                $exportRows[] = [
                    self::stringValue($row['name'] ?? ''),
                    self::stringValue($row['phone'] ?? ''),
                    self::stringValue($row['addr'] ?? ''),
                    self::stringValue($row['tag'] ?? ''),
                    self::stringValue($row['rating'] ?? ''),
                    $keyword,
                    self::formatExportTime($row['create_time'] ?? ''),
                ];
            }

            $fileName = '地图获客线索-' . date('Y-m-d-His') . '.xlsx';
            $url = self::writeExportExcel($exportRows, $fileName);

            self::$returnData = [
                'url'       => $url,
                'file_name' => $fileName,
            ];
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function collectConversationCardsExportRows(string $conversationId): array
    {
        $messages = MapLeadMessage::field('id,extra,create_time')
            ->where('conversation_id', $conversationId)
            ->where('user_id', self::$uid)
            ->where('content_type', self::CONTENT_CARDS)
            ->order('id', 'asc')
            ->select();

        $rows = [];
        foreach ($messages as $cardMessage) {
            $extra = self::decodeJson($cardMessage->extra ?? '');
            if (!isset($extra['cards']) || !is_array($extra['cards'])) {
                continue;
            }
            $rows = array_merge(
                $rows,
                self::cardsToExportRows($extra['cards'], (string)($cardMessage->create_time ?? ''))
            );
        }

        return $rows;
    }

    private static function buildExportKeyword(MapLeadConversation $conversation, array $extra): string
    {
        $title = '';
        if ($conversation && !$conversation->isEmpty()) {
            $title = self::stringValue($conversation->title ?? '');
        }
        if ($title !== '') {
            return self::limitString($title, 255);
        }

        $request = is_array($extra['request'] ?? null) ? $extra['request'] : [];
        $composed = trim(
            self::stringValue($request['city'] ?? '') .
            self::stringValue($request['region'] ?? '') .
            self::stringValue($request['location_extra'] ?? '') .
            (self::stringValue($request['biz'] ?? '') !== '' ? ' ' . self::stringValue($request['biz'] ?? '') : '')
        );
        if ($composed !== '') {
            return self::limitString($composed, 255);
        }

        $query = self::stringValue($request['query'] ?? '');
        return self::limitString($query, 255);
    }

    private static function cardsToExportRows(array $cards, string $fallbackTime): array
    {
        $rows = [];
        foreach ($cards as $card) {
            if (!is_array($card)) {
                continue;
            }
            $rows[] = [
                'name'        => self::stringValue($card['name'] ?? ''),
                'phone'       => self::stringValue($card['phone'] ?? ''),
                'addr'        => self::stringValue($card['addr'] ?? ''),
                'tag'         => self::stringValue($card['tag'] ?? ''),
                'rating'      => self::stringValue($card['rating'] ?? ''),
                'create_time' => $fallbackTime,
            ];
        }
        return $rows;
    }

    private static function formatExportTime(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_numeric($value)) {
            $ts = (int)$value;
            return $ts > 0 ? date('Y-m-d H:i:s', $ts) : '';
        }
        $text = trim((string)$value);
        if ($text === '') {
            return '';
        }
        $ts = strtotime($text);
        return $ts !== false ? date('Y-m-d H:i:s', $ts) : $text;
    }

    private static function writeExportExcel(array $rows, string $fileName): string
    {
        $headers = ['商家名称', '联系电话', '地址', '行业分类', '评分', '搜索关键词', '抓取时间'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('地图获客线索');

        foreach ($headers as $index => $header) {
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
        }

        $rowIndex = 2;
        foreach ($rows as $row) {
            foreach ($row as $colIndex => $value) {
                $cellValue = (string)$value;
                if (is_numeric($cellValue) && strlen($cellValue) >= 12) {
                    $cellValue .= "\t";
                }
                $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex, $cellValue);
            }
            $rowIndex++;
        }

        $exportCache = new ExportCache();
        $src = $exportCache->getSrc();
        if (!is_dir($src) && !mkdir($src, 0775, true) && !is_dir($src)) {
            throw new \Exception('导出目录创建失败');
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($src . $fileName);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        $vars = ['file' => $exportCache->setFile($fileName)];
        return (string)(url('adminapi/download/export', $vars, true, true));
    }

    private static function createConversationAndUserMessage(array $request): array
    {
        Db::startTrans();
        try {
            $time = time();
            $conversationId = $request['conversation_id'];
            if ($conversationId === '') {
                $conversationId = self::makeConversationId();
                $conversation = MapLeadConversation::create([
                    'conversation_id' => $conversationId,
                    'user_id'         => self::$uid,
                    'title'           => self::buildTitle($request),
                    'last_content'    => self::limitString($request['content'], 500),
                    'lead_count'      => 0,
                    'status'          => self::STATUS_PROCESSING,
                    'fail_reason'     => '',
                    'create_time'     => $time,
                    'update_time'     => $time,
                ]);
            } else {
                $conversation = MapLeadConversation::where('conversation_id', $conversationId)
                    ->where('user_id', self::$uid)
                    ->findOrEmpty();
                if ($conversation->isEmpty()) {
                    throw new \Exception('会话不存在或无权限访问');
                }
                $conversation->save([
                    'last_content' => self::limitString($request['content'], 500),
                    'status'       => self::STATUS_PROCESSING,
                    'fail_reason'  => '',
                    'update_time'  => $time,
                ]);
            }

            $userMessage = MapLeadMessage::create([
                'conversation_id' => $conversationId,
                'user_id'         => self::$uid,
                'role'            => self::ROLE_USER,
                'content_type'    => self::CONTENT_TEXT,
                'content'         => self::limitString($request['content'], 500),
                'status'          => self::STATUS_SUCCESS,
                'extra'           => self::jsonEncode([]),
                'create_time'     => $time,
                'update_time'     => $time,
            ]);

            Db::commit();
            return [$conversation, $userMessage];
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    private static function requestRemote(array $request): array
    {
        $payload = self::remoteRequest($request);
        Log::channel('map')->write('[地图获客][远端请求] 发起检索 | ' . json_encode([
            'uid'          => (int)self::$uid,
            'query'        => self::stringValue($payload['query'] ?? ''),
            'city'         => self::stringValue($payload['city'] ?? ''),
            'types'        => self::stringValue($payload['types'] ?? ''),
            'biz'          => self::stringValue($payload['biz'] ?? ''),
            'target_count' => (int)($payload['target_count'] ?? 0),
            'page'         => (int)($payload['page'] ?? 0),
            'page_size'    => (int)($payload['page_size'] ?? 0),
        ], JSON_UNESCAPED_UNICODE));

        try {
            return ToolsService::MapLead()->search($payload);
        } catch (\Throwable $e) {
            Log::channel('map')->error('[地图获客][远端异常] 检索调用失败 | ' . json_encode([
                'uid'     => (int)self::$uid,
                'query'   => self::stringValue($payload['query'] ?? ''),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ], JSON_UNESCAPED_UNICODE));
            return [
                'code'    => 0,
                'message' => $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    /**
     * 首次检索为空时，基于 parsed 做 F1/F2/F3 降级重试并合并去重。
     *
     * @return array{response: array, data: array, parsed: array, cards: array}
     */
    private static function searchWithFallback(array $request): array
    {
        $response = self::requestRemote($request);
        if ((int)($response['code'] ?? 0) !== 10000) {
            Log::channel('map')->write('[地图获客][首次检索] 远端返回失败 | ' . json_encode([
                'uid'     => (int)self::$uid,
                'code'    => (int)($response['code'] ?? 0),
                'message' => self::responseMessage($response),
            ], JSON_UNESCAPED_UNICODE));
            return [
                'response' => $response,
                'data'     => is_array($response['data'] ?? null) ? $response['data'] : [],
                'parsed'   => [],
                'cards'    => [],
            ];
        }

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $parsed = is_array($data['parsed'] ?? null) ? $data['parsed'] : [];
        $cards = self::normalizeCards(is_array($data['cards'] ?? null) ? $data['cards'] : []);
        $leadCount = (int)($data['lead_count'] ?? count($cards));
        $targetCount = max(1, (int)($request['target_count'] ?? 20));

        Log::channel('map')->write('[地图获客][首次检索] 远端返回成功 | ' . json_encode([
            'uid'        => (int)self::$uid,
            'code'       => (int)($response['code'] ?? 0),
            'lead_count' => $leadCount,
            'cards'      => count($cards),
        ], JSON_UNESCAPED_UNICODE));

        $fallbackUsed = false;
        $fallbackAttempts = [];

        if (($leadCount <= 0 || empty($cards)) && self::canFallback($parsed, $request)) {
            Log::channel('map')->write('[地图获客][降级启动] 空结果，开始降级重试 | ' . json_encode([
                'uid'          => (int)self::$uid,
                'lead_count'   => $leadCount,
                'cards'        => count($cards),
                'target_count' => $targetCount,
                'city'         => self::stringValue($parsed['city'] ?? $request['city'] ?? ''),
                'types'        => self::stringValue($parsed['types'] ?? $request['types'] ?? ''),
            ], JSON_UNESCAPED_UNICODE));
            $fallbackUsed = true;
            $f1Hit = false;

            foreach (self::buildFallbackRequests($request, $parsed) as $item) {
                $strategy = (string)($item['strategy'] ?? '');
                $fallbackRequest = is_array($item['request'] ?? null) ? $item['request'] : [];

                if ($strategy === 'F2' && $f1Hit) {
                    break;
                }
                if ($strategy === 'F3' && !empty($cards)) {
                    break;
                }
                if ($strategy === 'F2' && count($cards) >= $targetCount) {
                    break;
                }

                $attemptResponse = self::requestRemote($fallbackRequest);
                $attemptData = is_array($attemptResponse['data'] ?? null) ? $attemptResponse['data'] : [];
                $attemptCards = [];
                $attemptLeadCount = 0;
                if ((int)($attemptResponse['code'] ?? 0) === 10000) {
                    $attemptCards = self::normalizeCards(is_array($attemptData['cards'] ?? null) ? $attemptData['cards'] : []);
                    $attemptLeadCount = (int)($attemptData['lead_count'] ?? count($attemptCards));
                    if ($attemptLeadCount > 0 || !empty($attemptCards)) {
                        $cards = self::mergeUniqueCards($cards, $attemptCards, $targetCount);
                        $response = $attemptResponse;
                        $data = $attemptData;
                        if (empty($parsed) && is_array($attemptData['parsed'] ?? null)) {
                            $parsed = $attemptData['parsed'];
                        }
                        if ($strategy === 'F1') {
                            $f1Hit = !empty($cards);
                        }
                    }
                }

                $attemptCode = (int)($attemptResponse['code'] ?? 0);
                $fallbackAttempts[] = [
                    'strategy'   => $strategy,
                    'query'      => self::stringValue($fallbackRequest['query'] ?? ''),
                    'code'       => $attemptCode,
                    'lead_count' => $attemptLeadCount,
                ];
                Log::channel('map')->write('[地图获客][降级重试] 单次降级结果 | ' . json_encode([
                    'uid'        => (int)self::$uid,
                    'strategy'   => $strategy,
                    'query'      => self::stringValue($fallbackRequest['query'] ?? ''),
                    'code'       => $attemptCode,
                    'lead_count' => $attemptLeadCount,
                    'merged'     => count($cards),
                ], JSON_UNESCAPED_UNICODE));

                if ($strategy === 'F1' && $f1Hit) {
                    break;
                }
                if ($strategy === 'F2' && count($cards) >= $targetCount) {
                    break;
                }
                if ($strategy === 'F3' && !empty($cards)) {
                    break;
                }
            }
        }

        $finalCount = count($cards);
        $data['cards'] = self::publicCards($cards);
        $data['lead_count'] = $finalCount;
        $data['total_count'] = max((int)($data['total_count'] ?? 0), $finalCount);
        $data['parsed'] = $parsed;
        $data['fallback_used'] = $fallbackUsed;
        $data['fallback_attempts'] = $fallbackAttempts;

        Log::channel('map')->write('[地图获客][检索结束] 合并完成 | ' . json_encode([
            'uid'           => (int)self::$uid,
            'lead_count'    => $finalCount,
            'fallback_used' => $fallbackUsed,
            'attempts'      => count($fallbackAttempts),
        ], JSON_UNESCAPED_UNICODE));

        return [
            'response' => $response,
            'data'     => $data,
            'parsed'   => $parsed,
            'cards'    => $cards,
        ];
    }

    private static function canFallback(array $parsed, array $request): bool
    {
        $city = self::stringValue($parsed['city'] ?? $request['city'] ?? '');
        $types = self::stringValue($parsed['types'] ?? $request['types'] ?? '');
        $biz = self::stringValue($parsed['biz'] ?? $request['biz'] ?? '');
        $query = self::cleanQuery(self::stringValue($request['query'] ?? $request['content'] ?? ''));

        if ($city !== '') {
            return true;
        }
        if ($types !== '') {
            return true;
        }
        if ($biz !== '' && $biz !== '工厂') {
            return true;
        }
        return $query !== '';
    }

    /**
     * 构造 F1/F2/F3 降级请求列表。
     *
     * @return array<int, array{strategy: string, request: array}>
     */
    private static function buildFallbackRequests(array $request, array $parsed): array
    {
        $city = self::stringValue($parsed['city'] ?? $request['city'] ?? '');
        $biz = self::stringValue($parsed['biz'] ?? $request['biz'] ?? '');
        $types = self::splitTypes(self::stringValue($parsed['types'] ?? $request['types'] ?? ''));
        $attempts = [];
        $seenQueries = [];

        $primaryKeyword = $types[0] ?? '';
        if ($primaryKeyword === '' && $biz !== '' && $biz !== '工厂') {
            $primaryKeyword = $biz;
        }
        if ($primaryKeyword === '') {
            $primaryKeyword = self::cleanQuery(self::stringValue($request['query'] ?? $request['content'] ?? ''));
            if ($city !== '' && $primaryKeyword !== '' && mb_strpos($primaryKeyword, $city) === 0) {
                $primaryKeyword = self::stringValue(mb_substr($primaryKeyword, mb_strlen($city)));
            }
        }

        if ($primaryKeyword !== '') {
            $query = $city !== '' ? ($city . $primaryKeyword) : $primaryKeyword;
            self::appendFallbackAttempt($attempts, $seenQueries, 'F1', $request, $query, $city);
        }

        foreach (array_slice($types, 1) as $type) {
            if ($type === '' || $type === $primaryKeyword) {
                continue;
            }
            $query = $city !== '' ? ($city . $type) : $type;
            self::appendFallbackAttempt($attempts, $seenQueries, 'F2', $request, $query, $city);
        }

        $industry = self::normalizeIndustryKeyword($types, $biz);
        if ($industry !== '') {
            $query = ($city !== '' ? $city : '') . $industry . '厂';
            self::appendFallbackAttempt($attempts, $seenQueries, 'F3', $request, $query, $city);
        }

        return $attempts;
    }

    private static function appendFallbackAttempt(
        array &$attempts,
        array &$seenQueries,
        string $strategy,
        array $baseRequest,
        string $query,
        string $city
    ): void {
        $query = self::limitString(self::stringValue($query), 255);
        if ($query === '' || isset($seenQueries[$query])) {
            return;
        }
        $seenQueries[$query] = true;
        $attempts[] = [
            'strategy' => $strategy,
            'request'  => self::makeFallbackRequest($baseRequest, $query, $city),
        ];
    }

    private static function makeFallbackRequest(array $baseRequest, string $query, string $city): array
    {
        return [
            'conversation_id' => $baseRequest['conversation_id'] ?? '',
            'content'         => $query,
            'query'           => $query,
            'target_count'    => (int)($baseRequest['target_count'] ?? 20),
            'page'            => 1,
            'page_size'       => (int)($baseRequest['page_size'] ?? 25),
            'biz'             => '',
            'city'            => $city,
            'region'          => '',
            'location_extra'  => '',
            'types'           => '',
        ];
    }

    private static function splitTypes(string $types): array
    {
        if ($types === '') {
            return [];
        }
        $parts = preg_split('/[,，、\/|]+/u', $types) ?: [];
        $result = [];
        foreach ($parts as $part) {
            $value = self::stringValue($part);
            if ($value === '' || in_array($value, $result, true)) {
                continue;
            }
            $result[] = $value;
        }
        return $result;
    }

    private static function cleanQuery(string $query): string
    {
        $query = self::stringValue($query);
        if ($query === '') {
            return '';
        }
        $patterns = [
            '/帮我找/u',
            '/请帮我找/u',
            '/帮我搜索/u',
            '/都行/u',
            '/要工厂电话/u',
            '/要电话/u',
            '/电话/u',
            '/[，,。.!！？?\s]+/u',
        ];
        $query = preg_replace($patterns, '', $query) ?? $query;
        return self::stringValue($query);
    }

    private static function normalizeIndustryKeyword(array $types, string $biz): string
    {
        $joined = implode('', $types) . $biz;
        if ($joined !== '' && mb_strpos($joined, '泵') !== false) {
            return '水泵';
        }

        foreach ($types as $type) {
            $value = self::stringValue($type);
            if ($value === '') {
                continue;
            }
            if (mb_strpos($value, '生产') === 0) {
                $value = self::stringValue(mb_substr($value, 2));
            }
            if ($value !== '' && $value !== '工厂') {
                return $value;
            }
        }

        $biz = self::stringValue($biz);
        if ($biz !== '' && $biz !== '工厂') {
            return $biz;
        }
        return '';
    }

    private static function mergeUniqueCards(array $existing, array $incoming, int $limit): array
    {
        $map = [];
        foreach (array_merge($existing, $incoming) as $card) {
            if (!is_array($card)) {
                continue;
            }
            $key = self::stringValue($card['key'] ?? '');
            if ($key === '') {
                $key = md5(
                    self::stringValue($card['name'] ?? '') . '|' . self::stringValue($card['location'] ?? '')
                );
            }

            if (!isset($map[$key])) {
                $map[$key] = $card;
                continue;
            }

            $oldPhone = self::stringValue($map[$key]['phone'] ?? '');
            $newPhone = self::stringValue($card['phone'] ?? '');
            if ($oldPhone === '' && $newPhone !== '') {
                $map[$key] = $card;
            }
        }

        $list = array_values($map);
        usort($list, static function (array $a, array $b): int {
            $aHasPhone = trim((string)($a['phone'] ?? '')) !== '' ? 0 : 1;
            $bHasPhone = trim((string)($b['phone'] ?? '')) !== '' ? 0 : 1;
            return $aHasPhone <=> $bHasPhone;
        });

        if ($limit < 1) {
            return $list;
        }
        return array_slice($list, 0, $limit);
    }

    private static function createAssistantErrorMessage(
        MapLeadConversation $conversation,
        array $request,
        string $message,
        array $response
    ): MapLeadMessage {
        Db::startTrans();
        try {
            $time = time();
            $message = self::limitString($message, 1000);
            $assistantMessage = MapLeadMessage::create([
                'conversation_id' => $conversation->conversation_id,
                'user_id'         => self::$uid,
                'role'            => self::ROLE_ASSISTANT,
                'content_type'    => self::CONTENT_ERROR,
                'content'         => $message,
                'status'          => self::STATUS_FAIL,
                'extra'           => self::jsonEncode([
                    'request'  => self::remoteRequest($request),
                    'response' => self::responseSummary($response, [], []),
                ]),
                'create_time'     => $time,
                'update_time'     => $time,
            ]);

            $conversation->save([
                'last_content' => $message,
                'status'       => self::STATUS_FAIL,
                'fail_reason'  => self::limitString($message, 500),
                'update_time'  => $time,
            ]);

            Db::commit();
            return $assistantMessage;
        } catch (\Throwable $e) {
            Db::rollback();
            try {
                $conversation->save([
                    'status'      => self::STATUS_FAIL,
                    'fail_reason' => self::limitString($e->getMessage(), 500),
                    'update_time' => time(),
                ]);
            } catch (\Throwable $saveException) {
                Log::channel('map')->error('[地图获客][会话回写失败] 错误助手消息事务失败后状态保存失败 | ' . json_encode([
                    'uid'             => (int)self::$uid,
                    'conversation_id' => (string)$conversation->conversation_id,
                    'message'         => $saveException->getMessage(),
                ], JSON_UNESCAPED_UNICODE));
            }
            throw $e;
        }
    }

    private static function createAssistantCardsMessage(
        MapLeadConversation $conversation,
        array $request,
        array $response,
        array $data,
        array $parsed,
        array $cards,
        float $billingUnit
    ): MapLeadMessage {
        Db::startTrans();
        try {
            $time = time();
            $leadCount = (int)($data['lead_count'] ?? count($cards));
            $extra = self::responseSummary($response, $data, $parsed);
            $extra['request'] = self::remoteRequest($request);

            $assistantMessage = MapLeadMessage::create([
                'conversation_id' => $conversation->conversation_id,
                'user_id'         => self::$uid,
                'role'            => self::ROLE_ASSISTANT,
                'content_type'    => self::CONTENT_CARDS,
                'content'         => '',
                'status'          => self::STATUS_SUCCESS,
                'extra'           => self::jsonEncode($extra),
                'create_time'     => $time,
                'update_time'     => $time,
            ]);

            $billing = self::chargeCards((int)$assistantMessage->id, (string)$conversation->conversation_id, $cards, $billingUnit);
            $extra['cards'] = self::publicCards($cards);
            $extra['billing'] = $billing;

            $assistantMessage->save([
                'extra'       => self::jsonEncode($extra),
                'update_time' => $time,
            ]);

            $conversation->save([
                'title'        => $conversation->title ?: self::buildTitle($request, $parsed),
                'last_content' => self::buildResultText($leadCount),
                'lead_count'   => (int)$conversation->lead_count + $leadCount,
                'status'       => self::STATUS_SUCCESS,
                'fail_reason'  => '',
                'update_time'  => $time,
            ]);

            Db::commit();
            return $assistantMessage;
        } catch (\Throwable $e) {
            Db::rollback();
            try {
                $conversation->save([
                    'status'      => self::STATUS_FAIL,
                    'fail_reason' => self::limitString($e->getMessage(), 500),
                    'update_time' => time(),
                ]);
            } catch (\Throwable $saveException) {
                Log::channel('map')->error('[地图获客][会话回写失败] 卡片助手消息事务失败后状态保存失败 | ' . json_encode([
                    'uid'             => (int)self::$uid,
                    'conversation_id' => (string)$conversation->conversation_id,
                    'message'         => $saveException->getMessage(),
                ], JSON_UNESCAPED_UNICODE));
            }
            throw $e;
        }
    }

    private static function saveRecords(string $conversationId, int $messageId, array $cards): void
    {
        if (empty($cards)) {
            return;
        }

        try {
            MapLeadRecord::insertAll(self::buildRecords($conversationId, $messageId, $cards));
            Log::channel('map')->write('[地图获客][线索落库] 保存成功 | ' . json_encode([
                'uid'             => (int)self::$uid,
                'conversation_id' => $conversationId,
                'message_id'      => $messageId,
                'count'           => count($cards),
            ], JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            Log::channel('map')->error('[地图获客][线索落库] 保存失败 | ' . json_encode([
                'uid'             => (int)self::$uid,
                'conversation_id' => $conversationId,
                'message_id'      => $messageId,
                'count'           => count($cards),
                'message'         => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE));
        }
    }

    private static function normalizeChatParams(array $params): array
    {
        $content = self::stringValue($params['content'] ?? '');
        $query = self::stringValue($params['query'] ?? '');
        $biz = self::stringValue($params['biz'] ?? '');

        if ($content === '' && $query !== '') {
            $content = $query;
        }
        if ($query === '' && $content !== '') {
            $query = $content;
        }

        $request = [
            'conversation_id' => self::stringValue($params['conversation_id'] ?? ''),
            'content'         => $content,
            'query'           => $query,
            'target_count'    => self::intValue($params['target_count'] ?? 20, 20),
            'page'            => self::intValue($params['page'] ?? 1, 1),
            'page_size'       => self::intValue($params['page_size'] ?? 25, 25),
            'biz'             => $biz,
            'city'            => self::stringValue($params['city'] ?? ''),
            'region'          => self::stringValue($params['region'] ?? ''),
            'location_extra'  => self::stringValue($params['location_extra'] ?? ''),
            'types'           => self::stringValue($params['types'] ?? ''),
        ];

        if ($request['content'] === '' && $request['query'] === '' && $request['biz'] === '') {
            throw new \Exception('消息内容、搜索内容和商家类型不能同时为空');
        }
        if ($request['content'] === '') {
            $request['content'] = self::buildTitle($request);
        }
        if ($request['target_count'] < 1 || $request['target_count'] > 200) {
            throw new \Exception('目标线索数范围为1-200');
        }
        if ($request['page'] < 1) {
            throw new \Exception('页码不能小于1');
        }
        if ($request['page_size'] < 1 || $request['page_size'] > 25) {
            throw new \Exception('每页数量范围为1-25');
        }

        return $request;
    }

    private static function checkBillingBalance(array $request, float $unit): void
    {
        $estimateCount = min((int)$request['target_count'], (int)$request['page_size']);
        $need = round($estimateCount * $unit, 2);
        if ($need <= 0) {
            return;
        }

        $user = User::findOrEmpty(self::$uid);
        if ($user->isEmpty()) {
            throw new \Exception('用户查询失败');
        }
        // 团队被停用/成员到期拦截 + 团队感知余额校验(成员=企业钱包)
        \app\common\service\TeamMemberService::assertActive((int)self::$uid);
        if (\app\common\service\TeamBillingService::spendableTokens((int)self::$uid) < $need) {
            $msg = \app\common\service\TeamBillingService::resolveSpender((int)self::$uid) !== null
                ? '当前团队算力不足，请联系团队主' : '用户算力不足';
            Log::channel('map')->warning('[地图获客][余额不足] 预估扣费校验未通过 | ' . json_encode([
                'uid'          => (int)self::$uid,
                'need'         => $need,
                'unit'         => $unit,
                'target_count' => (int)$request['target_count'],
                'page_size'    => (int)$request['page_size'],
                'message'      => $msg,
            ], JSON_UNESCAPED_UNICODE));
            throw new \Exception($msg, 4059);
        }
    }

    private static function chargeCards(int $messageId, string $conversationId, array $cards, float $unit): array
    {
        $cardCount = count($cards);
        $points = round($cardCount * $unit, 2);
        $billing = [
            'scene'        => self::BILLING_SCENE,
            'card_count'   => $cardCount,
            'unit'         => $unit,
            'points'       => $points,
            'charged'      => false,
        ];

        if ($points <= 0) {
            return $billing;
        }

        // 团队感知余额校验(成员=企业钱包);实际扣费由 userTokensChange 走团队计费
        if (\app\common\service\TeamBillingService::spendableTokens((int)self::$uid) < $points) {
            $msg = \app\common\service\TeamBillingService::resolveSpender((int)self::$uid) !== null
                ? '当前团队算力不足，请联系团队主' : '用户算力不足';
            Log::channel('map')->warning('[地图获客][余额不足] 实际扣费校验未通过 | ' . json_encode([
                'uid'             => (int)self::$uid,
                'conversation_id' => $conversationId,
                'message_id'      => $messageId,
                'card_count'      => $cardCount,
                'need'            => $points,
                'unit'            => $unit,
                'message'         => $msg,
            ], JSON_UNESCAPED_UNICODE));
            throw new \Exception($msg, 4059);
        }

        User::userTokensChange((int)self::$uid, $points);

        $extra = [
            '扣费项目'     => '地图获客',
            'conversation_id' => $conversationId,
            'message_id'      => $messageId,
            '卡片数量'     => $cardCount,
            '算力单价'     => $unit . '算力/条',
            '实际消耗算力' => $points,
        ];
        AccountLogLogic::recordUserTokensLog(
            true,
            (int)self::$uid,
            AccountLogEnum::TOKENS_DEC_MAP_CHAT_CLUES,
            $points,
            (string)$messageId,
            $extra
        );

        $billing['charged'] = true;
        Log::channel('map')->write('[地图获客][扣费成功] 算力已扣除 | ' . json_encode([
            'uid'             => (int)self::$uid,
            'conversation_id' => $conversationId,
            'message_id'      => $messageId,
            'card_count'      => $cardCount,
            'unit'            => $unit,
            'points'          => $points,
        ], JSON_UNESCAPED_UNICODE));
        return $billing;
    }

    private static function billingUnit(): float
    {
        $unit = (float)ModelConfig::where('scene', self::BILLING_SCENE)
            ->where('status', 1)
            ->value('score', self::DEFAULT_CARD_UNIT);
        if ($unit <= 0) {
            Log::channel('map')->warning('[地图获客][计费配置] 算力单价未配置，使用默认1算力/条 | ' . json_encode([
                'uid'   => (int)self::$uid,
                'scene' => self::BILLING_SCENE,
                'unit'  => self::DEFAULT_CARD_UNIT,
            ], JSON_UNESCAPED_UNICODE));
            return self::DEFAULT_CARD_UNIT;
        }
        return $unit;
    }

    private static function remoteRequest(array $request): array
    {
        return [
            'query'          => $request['query'],
            'target_count'   => $request['target_count'],
            'page'           => $request['page'],
            'page_size'      => $request['page_size'],
            'biz'            => $request['biz'],
            'city'           => $request['city'],
            'region'         => $request['region'],
            'location_extra' => $request['location_extra'],
            'types'          => $request['types'],
        ];
    }

    private static function normalizeCards(array $cards): array
    {
        $result = [];

        foreach ($cards as $card) {
            if (!is_array($card)) {
                continue;
            }

            $name = self::stringValue($card['name'] ?? '');
            $location = self::stringValue($card['location'] ?? '');
            $key = self::stringValue($card['key'] ?? '');
            if ($key === '') {
                $key = md5($name . '|' . $location);
            }

            [$lng, $lat] = self::parseLocation($location);
            $result[] = [
                'key'       => self::limitString($key, 128),
                'name'      => self::limitString($name, 255),
                'addr'      => self::limitString(self::stringValue($card['addr'] ?? ''), 500),
                'phone'     => self::limitString(self::stringValue($card['phone'] ?? ''), 255),
                'tag'       => self::limitString(self::stringValue($card['tag'] ?? ''), 255),
                'rating'    => self::limitString(self::stringValue($card['rating'] ?? ''), 20),
                'location'  => self::limitString($location, 64),
                'lng'       => $lng,
                'lat'       => $lat,
                '_raw_data' => $card,
            ];
        }

        return $result;
    }

    private static function buildRecords(string $conversationId, int $messageId, array $cards): array
    {
        $records = [];
        $time = time();

        foreach ($cards as $card) {
            $records[] = [
                'conversation_id' => $conversationId,
                'message_id'      => $messageId,
                'user_id'         => self::$uid,
                'poi_key'         => $card['key'],
                'name'            => $card['name'],
                'addr'            => $card['addr'],
                'phone'           => $card['phone'],
                'tag'             => $card['tag'],
                'rating'          => $card['rating'],
                'location'        => $card['location'],
                'lng'             => $card['lng'],
                'lat'             => $card['lat'],
                'raw_data'        => self::jsonEncode(is_array($card['_raw_data'] ?? null) ? $card['_raw_data'] : $card),
                'create_time'     => $time,
                'update_time'     => $time,
            ];
        }

        return $records;
    }

    private static function formatMessage(MapLeadMessage $message, array $cards = []): array
    {
        $data = $message->toArray();
        if (!is_array($data['extra'] ?? null)) {
            $data['extra'] = self::decodeJson($data['extra'] ?? '');
        }
        $data['cards'] = $cards;
        if (empty($data['cards']) && isset($data['extra']['cards']) && is_array($data['extra']['cards'])) {
            $data['cards'] = $data['extra']['cards'];
        }
        if (isset($data['extra']['cards'])) {
            unset($data['extra']['cards']);
        }
        return [
            'id'              => $data['id'] ?? 0,
            'conversation_id' => $data['conversation_id'] ?? '',
            'role'            => $data['role'] ?? '',
            'content_type'    => $data['content_type'] ?? '',
            'content'         => $data['content'] ?? '',
            'status'          => $data['status'] ?? self::STATUS_SUCCESS,
            'extra'           => $data['extra'] ?? [],
            'cards'           => $data['cards'] ?? [],
            'create_time'     => $data['create_time'] ?? '',
        ];
    }

    private static function publicCards(array $cards): array
    {
        foreach ($cards as &$card) {
            unset($card['_raw_data']);
        }
        unset($card);

        return $cards;
    }

    private static function responseSummary(array $response, array $data, array $parsed): array
    {
        $summary = [
            'code'        => $response['code'] ?? null,
            'message'     => $response['message'] ?? ($response['msg'] ?? ''),
            'parsed'      => $parsed,
            'lead_count'  => (int)($data['lead_count'] ?? 0),
            'total_count' => (int)($data['total_count'] ?? 0),
            'next_page'   => (int)($data['next_page'] ?? 0),
            'exhausted'   => !empty($data['exhausted']),
        ];

        if (array_key_exists('fallback_used', $data)) {
            $summary['fallback_used'] = !empty($data['fallback_used']);
            $summary['fallback_attempts'] = is_array($data['fallback_attempts'] ?? null)
                ? $data['fallback_attempts']
                : [];
        }

        return $summary;
    }

    private static function responseMessage(array $response): string
    {
        $message = $response['message'] ?? ($response['msg'] ?? '地图获客接口调用失败');
        if (is_array($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }
        return (string)$message;
    }

    private static function parseLocation(string $location): array
    {
        $parts = explode(',', $location);
        if (count($parts) < 2) {
            return [0, 0];
        }
        return [(float)$parts[0], (float)$parts[1]];
    }

    private static function buildTitle(array $request, array $parsed = []): string
    {
        if (($request['content'] ?? '') !== '') {
            return self::limitString($request['content'], 100);
        }
        if (($request['query'] ?? '') !== '') {
            return self::limitString($request['query'], 100);
        }

        $title = trim(
            self::stringValue($parsed['city'] ?? $request['city'] ?? '') .
            self::stringValue($parsed['region'] ?? $request['region'] ?? '') .
            self::stringValue($parsed['location_extra'] ?? $request['location_extra'] ?? '') .
            self::stringValue($parsed['biz'] ?? $request['biz'] ?? '')
        );

        return self::limitString($title !== '' ? $title : '地图获客' . date('YmdHis'), 100);
    }

    private static function buildResultText(int $leadCount): string
    {
        return $leadCount > 0 ? '找到 ' . $leadCount . ' 条线索' : '未找到相关线索';
    }

    private static function makeConversationId(): string
    {
        try {
            return 'ml_' . date('YmdHis') . bin2hex(random_bytes(8));
        } catch (\Throwable $e) {
            return 'ml_' . date('YmdHis') . mt_rand(100000, 999999);
        }
    }

    private static function intValue(mixed $value, int $default): int
    {
        if ($value === '' || $value === null) {
            return $default;
        }
        return (int)$value;
    }

    private static function stringValue(mixed $value): string
    {
        if (is_array($value)) {
            return '';
        }
        return trim((string)$value);
    }

    private static function limitString(string $value, int $length): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length, 'UTF-8');
        }
        return substr($value, 0, $length);
    }

    private static function jsonEncode(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private static function decodeJson(mixed $value): array
    {
        if (empty($value)) {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        $data = json_decode((string)$value, true);
        return is_array($data) ? $data : [];
    }
}
