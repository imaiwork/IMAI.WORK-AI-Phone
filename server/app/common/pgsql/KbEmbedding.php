<?php


namespace app\common\pgsql;

use app\common\service\FileService;
use think\Model;

class KbEmbedding extends Model
{
    protected $connection = 'pgsql';

    protected $pk     = 'uuid';
    protected $schema = [
        'uuid'         => 'uuid',       // 唯一ID
        'kb_id'        => 'int4',       // 知识库ID
        'fd_id'        => 'int4',       // 文件的ID
        'user_id'      => 'int4',       // 用户的ID
        'emb_model_id' => 'int4',       // 向量模型ID
        'index'        => 'int4',       // 下标索引
        'code'         => 'varchar',    // 批次编号
        'salt'         => 'varchar',    // 问题的盐
        'channel'      => 'varchar',    // 训练渠道: [openai,zhipu,xunfei,m3e]
        'model'        => 'varchar',    // 训练模型: [text-embedding-ada-002]
        'dimension'    => 'varchar',    // 向量维度
        'question'     => 'text',       // 问题
        'answer'       => 'text',       // 答复
        'annex'        => 'text',       // 附件
        'phrases'      => 'tsvector',   // 分词
        'embedding'    => 'vector',     // 向量
        'tokens'       => 'numeric',    // 消耗tokens
        'error'        => 'text',       // 错误信息
        'status'       => 'int2',       // 训练状态: [0=等待学习, 1=学习中, 2=学习成功, 3=学习失败]
        'is_delete'    => 'int2',       // 是否删除: [0=否, 1=是]
        'create_time'  => 'int4',       // 创建时间
        'update_time'  => 'int4',       // 更新时间
        'delete_time'  => 'int4'        // 删除时间
    ];

    public function join($join, $condition): static
    {
        unset($join);
        unset($condition);
        return $this;
    }

    public function bind($data): static
    {
        unset($data);
        return $this;
    }

    public function buildSql(): static
    {
        return $this;
    }

    /**
     * @notes 将分段 annex 解码为数组
     * @param mixed $annex
     * @return array
     */
    public static function decodeAnnex(mixed $annex): array
    {
        if (is_string($annex)) {
            $annex = trim($annex);
            if ($annex === '') {
                return [];
            }
            $decoded = json_decode($annex, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($annex) ? $annex : [];
    }

    /**
     * @notes 将分段 annex 格式化为搜索测试/详情使用的 images、video、files
     * @param mixed $annex null、空串、JSON 字符串或已解码数组
     * @return array{images: array, video: array, files: array}
     */
    public static function formatAnnex(mixed $annex): array
    {
        $annex = self::decodeAnnex($annex);

        return [
            'images' => self::formatAnnexItems($annex['images'] ?? []),
            'video'  => self::formatAnnexItems($annex['video'] ?? []),
            'files'  => self::formatAnnexItems($annex['files'] ?? []),
        ];
    }

    /**
     * @notes 训练/全文检索文本：question + answer + 附件名，不含 URL
     * @param mixed $question
     * @param mixed $answer
     * @param mixed $annex
     * @return string
     */
    public static function buildSearchableText(mixed $question, mixed $answer, mixed $annex = []): string
    {
        $chunks = [];
        $question = trim((string)$question);
        $answer = trim((string)$answer);
        if ($question !== '') {
            $chunks[] = $question;
        }
        if ($answer !== '') {
            $chunks[] = $answer;
        }
        $annex = self::decodeAnnex($annex);
        foreach (['images', 'video', 'files'] as $key) {
            $items = $annex[$key] ?? [];
            if (!is_array($items)) {
                continue;
            }
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $name = trim((string)($item['name'] ?? ''));
                if ($name !== '') {
                    $chunks[] = $name;
                }
            }
        }
        return implode("\n", $chunks);
    }

    /**
     * @notes 搜索测试历史记录缺素材字段时补空数组
     * @param mixed $lists
     * @return array
     */
    public static function fillSearchTestAnnex(mixed $lists): array
    {
        if (!is_array($lists)) {
            return [];
        }

        foreach ($lists as &$item) {
            if (!is_array($item)) {
                continue;
            }
            $item['images'] = is_array($item['images'] ?? null) ? $item['images'] : [];
            $item['video']  = is_array($item['video'] ?? null) ? $item['video'] : [];
            $item['files']  = is_array($item['files'] ?? null) ? $item['files'] : [];
        }
        unset($item);

        return $lists;
    }

    /**
     * @param mixed $items
     * @return array
     */
    private static function formatAnnexItems(mixed $items): array
    {
        if (!is_array($items)) {
            return [];
        }

        $result = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $result[] = [
                'name' => $item['name'] ?? '',
                'url'  => FileService::getFileUrl((string)($item['url'] ?? '')),
            ];
        }

        return $result;
    }

    /**
     * @notes 更新分词信息
     * @param array $uuids
     * @return void
     * @author fzr
     */
    public static function updateTsVector(array $uuids): void
    {
        if (empty($uuids)) {
            return;
        }
        $model = new self();
        $table = $model->getTable();
        $conn = app('db')->connect('pgsql');
        $rows = $model->whereIn('uuid', $uuids)->field(['uuid', 'question', 'answer', 'annex'])->select();
        foreach ($rows as $row) {
            $text = self::buildSearchableText($row['question'] ?? '', $row['answer'] ?? '', $row['annex'] ?? '');
            $uuid = str_replace(["\\", "'"], ["\\\\", "''"], (string)$row['uuid']);
            $escaped = str_replace(["\\", "'"], ["\\\\", "''"], $text);
            $sql = "UPDATE $table SET phrases = to_tsvector('zh_en', '{$escaped}') WHERE uuid = '{$uuid}'";
            $conn->query($sql);
        }
    }
}