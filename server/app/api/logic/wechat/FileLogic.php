<?php

namespace app\api\logic\wechat;

use app\common\model\wechat\AiWechatMediaGroup;
use app\common\model\wechat\AiWechatMediaFile;
use app\common\service\FileService;
use think\facade\Db;

/**
 * FileLogic
 * @desc 文件
 * @author Qasim
 */
class FileLogic extends WechatBaseLogic
{

    /**
     * @desc 添加分组
     * @param array $name 分组名称
     * @return bool
     */
    public static function addGroup(string $name)
    {
        try
        {
            $group = AiWechatMediaGroup::where('user_id', self::$uid)->where('group_name', $name)->findOrEmpty();
            if (!$group->isEmpty())
            {
                self::setError('分组名称已存在');
                return false;
            }

            $group = AiWechatMediaGroup::create([
                'user_id' => self::$uid,
                'group_name' => $name,
            ]);

            self::$returnData = $group->toArray();
            return true;
        }
        catch (\Exception $e)
        {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 删除分组
     * @param int $id
     * @param array $name 分组名称
     * @return bool
     */
    public static function updateGroup(int $id, string $name)
    {
        try
        {
            $group = AiWechatMediaGroup::where('user_id', self::$uid)->where('id', $id)->findOrEmpty();

            if ($group->isEmpty())
            {
                self::setError('分组不存在');
                return false;
            }

            if ($group->group_name != $name)
            {

                $temp = AiWechatMediaGroup::where('user_id', self::$uid)->where('group_name', $name)->findOrEmpty();
                if (!$temp->isEmpty())
                {
                    self::setError('分组名称已存在');
                    return false;
                }
            }

            $group->group_name = $name;
            $group->save();
            self::$returnData = $group->toArray();
            return true;
        }
        catch (\Exception $e)
        {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 删除分组
     * @param int $id
     * @return bool
     */
    public static function deleteGroup(int $id)
    {
        try
        {
            $group = AiWechatMediaGroup::where('user_id', self::$uid)->where('id', $id)->findOrEmpty();

            if ($group->isEmpty())
            {
                self::setError('分组不存在');
                return false;
            }

            // 存在关联文件
            $files = AiWechatMediaFile::whereRaw('JSON_CONTAINS(group_ids, ?)', [$group->id])->findOrEmpty();

            if (!$files->isEmpty())
            {
                self::setError('分组存在关联文件');
                return false;
            }

            $group->delete();

            self::$returnData = [];
            return true;
        }
        catch (\Exception $e)
        {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 添加文件
     * @param array $params
     * @return bool
     */
    public static function addFile(array $params)
    {
        Db::startTrans();
        try
        {
            // 分组ID
            $groupIds = AiWechatMediaGroup::where('user_id', self::$uid)->whereIn('id', $params['group_ids'])->column('id');

            if (!$groupIds)
            {
                self::setError('所选分组不存在');
                return false;
            }

            $data = [];

            foreach ($params['files'] as $file)
            {

                $data[] = [
                    'user_id' => self::$uid,
                    'file_name' => $file['name'],
                    'file_type' => $params['file_type'],
                    'file_url' => FileService::setFileUrl($file['url']),
                    'group_ids' => json_encode($groupIds),
                    'ext_info' => json_encode($params['ext_info'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'create_time' => time(),
                    'update_time' => time(),
                ];
            }
            AiWechatMediaFile::insertAll($data);
            Db::commit();
            self::$returnData = [];
            return true;
        }
        catch (\Exception $e)
        {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 更新文件
     * @param array $params
     * @return bool
     */
    public static function updateFile(array $params)
    {
        try
        {
            // 获取文件
            $file = AiWechatMediaFile::where('user_id', self::$uid)->whereIn('id', $params['id'])->findOrEmpty();

            if ($file->isEmpty())
            {
                self::setError('文件不存在');
                return false;
            }

            // 分组ID
            $groupIds = AiWechatMediaGroup::where('user_id', self::$uid)->whereIn('id', $params['group_ids'])->column('id');

            if (!$groupIds)
            {
                self::setError('所选分组不存在');
                return false;
            }

            $file->file_name = $params['file_name'];
            $file->file_url = FileService::setFileUrl($params['file_url']);
            $file->ext_info = $params['ext_info'];
            $file->group_ids = $groupIds;
            $file->save();

            self::$returnData = $file->toArray();
            return true;
        }
        catch (\Exception $e)
        {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 删除文件
     * @param array $ids
     * @return bool
     */
    public static function deleteFile(array $ids)
    {
        try
        {
            AiWechatMediaFile::where('user_id', self::$uid)->whereIn('id', $ids)->delete();

            self::$returnData = [];
            return true;
        }
        catch (\Exception $e)
        {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @desc 文件信息
     * @param int $id
     * @return bool
     */
    public static function fileInfo(int $id)
    {
        try
        {
            // 获取文件
            $file = AiWechatMediaFile::where('user_id', self::$uid)->whereIn('id', $id)->findOrEmpty();

            if ($file->isEmpty())
            {
                self::setError('文件不存在');
                return false;
            }

            self::$returnData = $file->toArray();
            return true;
        }
        catch (\Exception $e)
        {
            self::setError($e->getMessage());
            return false;
        }
    }
}
