<?php


namespace app\common\pgsql;

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
     * @notes 更新分词信息
     * @param array $uuids
     * @return void
     * @author fzr
     */
    public static function updateTsVector(array $uuids): void
    {
        if (!empty($uuids)) {
            $model = new self();
            $table = $model->getTable();

            $ids = "'" . implode("','", $uuids) . "'";
            $sql = "UPDATE $table ";
            $sql .= "SET phrases = to_tsvector('zh_en', question) || to_tsvector('zh_en', answer) ";
            $sql .= "WHERE uuid IN ($ids)";
            app('db')->connect('pgsql')->query($sql);
        }
    }
}