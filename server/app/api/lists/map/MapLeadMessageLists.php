<?php

namespace app\api\lists\map;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsExtendInterface;
use app\common\lists\ListsSearchInterface;
use app\common\model\map\MapLeadConversation;
use app\common\model\map\MapLeadMessage;
use app\common\model\map\MapLeadRecord;

class MapLeadMessageLists extends BaseApiDataLists implements ListsSearchInterface, ListsExtendInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['conversation_id'],
        ];
    }

    public function lists(): array
    {
        $messages = $this->baseQuery()
            ->field('id,conversation_id,role,content_type,content,status,extra,create_time')
            ->order('id', 'asc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        $messageIds = array_column($messages, 'id');
        $records = [];
        if (!empty($messageIds)) {
            $rows = MapLeadRecord::field('id,conversation_id,message_id,poi_key,name,addr,phone,tag,rating,location,lng,lat,create_time')
                ->where('user_id', $this->userId)
                ->whereIn('message_id', $messageIds)
                ->order('id', 'asc')
                ->select()
                ->toArray();

            foreach ($rows as $row) {
                $row['key'] = $row['poi_key'];
                $records[(int)$row['message_id']][] = $row;
            }
        }

        foreach ($messages as &$message) {
            if (!is_array($message['extra'] ?? null)) {
                $message['extra'] = $this->decodeJson($message['extra'] ?? '');
            }
            $message['cards'] = $records[(int)$message['id']] ?? [];
            if (empty($message['cards']) && isset($message['extra']['cards']) && is_array($message['extra']['cards'])) {
                $message['cards'] = $message['extra']['cards'];
            }
            if (isset($message['extra']['cards'])) {
                unset($message['extra']['cards']);
            }
        }
        unset($message);

        return $messages;
    }

    public function count(): int
    {
        return $this->baseQuery()->count();
    }

    public function extend()
    {
        $conversationId = trim((string)$this->request->get('conversation_id', ''));
        if ($conversationId === '') {
            return [];
        }

        return MapLeadConversation::field('id,conversation_id,title,last_content,lead_count,status,fail_reason,create_time,update_time')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $this->userId)
            ->findOrEmpty()
            ->toArray();
    }

    private function baseQuery()
    {
        return MapLeadMessage::where('user_id', $this->userId)
            ->where($this->searchWhere);
    }

    private function decodeJson($value): array
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
