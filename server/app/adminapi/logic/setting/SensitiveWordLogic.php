<?php


namespace app\adminapi\logic\setting;

use app\common\logic\BaseLogic;
use app\common\model\SensitiveWord;
use app\common\service\ConfigService;
use Exception;

/**
 * 敏感词逻辑类
 */
class SensitiveWordLogic extends BaseLogic
{
    /**
     * @notes 敏感词详情
     * @param int $id
     * @return array
     */
    public static function detail(int $id): array
    {
        return (new SensitiveWord())
            ->field(['id,word,status,create_time,update_time'])
            ->where(['id'=>$id])
            ->findOrEmpty()
            ->toArray();
    }

    /**
     * @notes 敏感词新增
     * @param array $post
     * @return bool
     * @author kb
     */
    public static function add(array $post): bool
    {
        try {
            $post['word'] = implode('；',$post['word']);
            SensitiveWord::create([
                'word'        => $post['word'],
                'status'      => $post['status']??0,
                'create_time' => time(),
                'update_time' => time()
            ]);
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 敏感词编辑
     * @param array $post
     * @return bool
     * @author kb
     */
    public static function edit(array $post): bool
    {
        try {
            $post['word'] = implode('；', $post['word']);
            SensitiveWord::update([
                'word'        => $post['word'],
                'status'      => $post['status']??0,
                'update_time' => time()
            ], ['id'=>intval($post['id'])]);
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 敏感词删除
     * @param int $id
     * @return bool
     * @author kb
     */
    public static function del(int $id): bool
    {
        try {
            SensitiveWord::destroy($id);
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 敏感词状态
     * @param int $id
     * @return bool
     * @author kb
     */
    public static function status(int $id): bool
    {
        try {
            $sensitiveWord = (new SensitiveWord())->findOrEmpty($id);
            if($sensitiveWord->isEmpty()){
                return true;
            }
            $sensitiveWord->status = $sensitiveWord->status ? 0 : 1;
            $sensitiveWord->save();
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取配置
     * @return array|int
     * @author kb
     */
    public static function getConfig(): array|int
    {
        $isSensitive = ConfigService::get('chat', 'is_sensitive', 1);
        $isSensitiveSystem = ConfigService::get('chat', 'is_sensitive_system', 1);
        return ['is_sensitive'=>$isSensitive, 'is_sensitive_system' => $isSensitiveSystem];
    }

    /**
     * @notes 设置配置
     * @param array $params
     * @author kb
     */
    public static function setConfig(array $params)
    {
        ConfigService::set('chat', 'is_sensitive', $params['is_sensitive']??0);
        ConfigService::set('chat', 'is_sensitive_system', $params['is_sensitive_system']??0);
    }
}