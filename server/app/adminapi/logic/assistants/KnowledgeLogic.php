<?php

namespace app\adminapi\logic\assistants;

use app\common\logic\BaseLogic;
use app\common\model\chat\Assistants;
use app\common\model\chat\Scene;
use app\common\service\ConfigService;
use app\common\service\FileService;

class KnowledgeLogic extends BaseLogic
{
    /**
     * 添加
     * @return bool
     */
    public static function enable(): bool
    {
        try
        {
            // 获取配置
            $config = ConfigService::get('assistant', 'knowledge') ?? [];

            if (!$config)
            {
                // $response = \app\common\service\ToolsService::Assistant()->createKnowledge();
                $response = [
                    "knowledge_id" => "0oas7c9g8z",
                    "category_id" => "cate_07084b327203405c956c090cc8e127cf_10439712",
                ];
                ConfigService::set('assistant', 'knowledge', $response);
            }

            return true;
        }
        catch (\Throwable $exception)
        {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * @notes 助手详情
     * @param int $assistantId
     * @return array
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public static function detail(int $assistantId): array
    {
        $info = Assistants::where('id', $assistantId)->findOrEmpty()->toArray();

        $info['logo'] = FileService::getFileUrl($info['logo']);

        return $info;
    }

    /**
     * @notes 助手详情
     * @param array $data
     * @return bool
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public static function edit(array $data): bool
    {
        try
        {
            $assistantInfo = Assistants::where('id', $data['id'])->findOrEmpty();

            if ($assistantInfo->isEmpty())
            {
                self::setError('获取助理失败');
                return false;
            }

            if ($assistantInfo->name != $data['name'])
            {
                // 检查助理名是否存在
                $assistant = Assistants::where('name', $data['name'])->findOrEmpty();
                if (!$assistant->isEmpty())
                {
                    self::setError('助理名称已存在');
                    return false;
                }
            }

            // 检查关联场景是否存在
            $scene = Scene::where('id', $data['scene_id'])->findOrEmpty();
            if ($scene->isEmpty())
            {
                self::setError('场景分类不存在');
                return false;
            }

            if (isset($data['logo']))
            {
                $data['logo'] = FileService::setFileUrl($data['logo']);
            }

            if (isset($data['preliminary_ask']))
            {
                $data['preliminary_ask'] = json_encode(json_decode($data['preliminary_ask'], true), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            if (isset($data['template_info']))
            {
                $data['template_info'] = json_encode(json_decode($data['template_info'], true), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            Assistants::where('id', $assistantInfo->id)->update($data);

            self::$returnData = $assistantInfo->refresh()->toArray();

            return true;
        }
        catch (\Throwable $exception)
        {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除助手
     * @param int $assistantId
     * @return bool
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public static function delete(int $assistantId): bool
    {
        try
        {
            $assistantInfo = Assistants::where('id', $assistantId)->findOrEmpty();
            if ($assistantInfo->isEmpty())
            {
                throw new \Exception("助手查找异常");
            }

            Assistants::destroy(['id' => $assistantId]);
            return true;
        }
        catch (\Throwable $exception)
        {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 修改状态
     * @param int $id
     * @return bool
     * @author L
     * @data 2024/7/2 15:14
     */
    public static function changeStatus(int $id): bool
    {
        try
        {
            $assistantsInfo = Assistants::findOrEmpty($id);
            if ($assistantsInfo->isEmpty())
            {
                throw new \Exception("助手查找异常");
            }
            $assistantsInfo->status = 1 - $assistantsInfo->status;
            $assistantsInfo->save();
            return true;
        }
        catch (\Exception $exception)
        {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * @notes 通用聊天
     * @author 段誉
     * @date 2022/9/20 15:30
     */
    public static function chat()
    {
        $assistant = Assistants::where('id', 1)->findOrEmpty();

        if ($assistant->isEmpty())
        {
            self::setError('助手查找异常');
            return false;
        }

        $preliminaryAsk = json_decode($assistant->preliminary_ask, true) ?? [];
        $extra          = json_decode($assistant->extra ?? '', true) ?? [];

        foreach ($preliminaryAsk as $key => $value)
        {

            if (isset($value['logo']))
            {

                $preliminaryAsk[$key]['logo'] = FileService::getFileUrl($value['logo']);
            }
        }

        $assistant->preliminary_ask     = $preliminaryAsk;
        $assistant->logo                = FileService::getFileUrl($assistant['logo']);
        $assistant->banner              = FileService::getFileUrl($extra['banner'] ?? '');
        $assistant->new_chat_prompt     = $extra['new_chat_prompt'] ?? '';
        $assistant->file_prompt         = $extra['file_prompt'] ?? '';
        $assistant->extra               = $extra;

        self::$returnData = $assistant->toArray();
        return true;
    }


    /**
     * @notes 更新通用聊天
     * @param array $post
     * @author 段誉
     * @date 2022/9/20 15:30
     */
    public static function updateChat(array $postData)
    {
        $assistant = Assistants::where('id', 1)->findOrEmpty();
        if ($assistant->isEmpty())
        {
            self::setError('助手查找异常');
            return false;
        }

        $preliminaryAsk = $postData['preliminary_ask'] ?? [];

        foreach ($preliminaryAsk as $key => $value)
        {

            if (isset($value['logo']))
            {

                $preliminaryAsk[$key]['logo'] = FileService::setFileUrl($value['logo']);
            }
        }

        $assistant->extra       = json_encode([
            'banner'            => FileService::setFileUrl($postData['banner']),
            'new_chat_prompt'   => $postData['new_chat_prompt'],
            'file_prompt'       => $postData['file_prompt'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $assistant->logo            = FileService::setFileUrl($postData['logo']);
        $assistant->preliminary_ask = json_encode($preliminaryAsk, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $assistant->save();
        self::$returnData = $assistant->toArray();
        return true;
    }
}
