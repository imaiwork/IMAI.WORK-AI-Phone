<?php

namespace app\api\logic\sv;

use app\common\model\sv\SvAccountKeyword;
use app\common\model\sv\SvAccount;
use app\common\service\FileService;

/**
 * AccountKeywordLogic
 * @desc 账号关键词
 * @author Qasim
 */
class AccountKeywordLogic extends SvBaseLogic
{

    /**
     * @desc 添加账号关键词
     * @param array $params
     * @return bool
     */
    public static function addAccountKeyword(array $params)
    {
        try {
            $params['user_id'] = self::$uid;

            // 检查账号是否存在
            $account = SvAccount::where('account', $params['account'])->where('type', $params['type'])->where('user_id', self::$uid)->findOrEmpty();
            if ($account->isEmpty()) {
                self::setError('账号不存在');
                return false;
            }

            // 检查关键词是否已添加
            $keyword = SvAccountKeyword::where('user_id', self::$uid)->where('type', $params['type'])->where('account', $params['account'])->where('keyword', $params['keyword'])->findOrEmpty();

            if (!$keyword->isEmpty()) {
                self::setError('关键词已添加');
                return false;
            }

            // 处理图片
            foreach ($params['reply'] as $key => $value) {
                if ($value['type'] == 1) {
                    $params['reply'][$key]['content'] = FileService::setFileUrl($value['content']);
                }
            }

            // 添加
            $account = SvAccountKeyword::create($params);

            self::$returnData = $account->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 更新账号关键词
     * @param array $params
     * @return bool
     */
    public static function updateAccountKeyword(array $params)
    {

        try {
            // 检查账号是否存在
            $account = SvAccountKeyword::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($account->isEmpty()) {
                self::setError('关键词不存在');
                return false;
            }

            // 处理图片
            foreach ($params['reply'] as $key => $value) {
                if ($value['type'] == 1) {
                    $params['reply'][$key]['content'] = FileService::setFileUrl($value['content']);
                }
            }

            $params['reply'] = json_encode($params['reply'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            // 更新
            SvAccountKeyword::where('id', $account->id)->update($params);

            self::$returnData = $account->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 删除账号关键词
     * @param array $params
     * @return bool
     */
    public static function deleteAccountKeyword(array $params)
    {
        try {
            // 检查账号关键词是否存在
            $account = SvAccountKeyword::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($account->isEmpty()) {
                self::setError('关键词不存在');
                return false;
            }

            $account->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 导入账号关键词
     * @param array $params
     * @return bool
     */
    public static function importAccountKeyword(array $params)
    {
        try {

            // 检查账号是否存在
            $account = SvAccount::where('account', $params['account'])->where('type', $params['type'])->where('user_id', self::$uid)->findOrEmpty();
            if ($account->isEmpty()) {
                self::setError('账号不存在');
                return false;
            }

            $fileContent = file_get_contents($params['file']);

            // 将csv文件内容转换为数组
            $fileContent = explode("\r\n", $fileContent);

            $content = [];

            foreach ($fileContent as $key => $value) {

                if ($key == 0) {
                    continue;
                }

                if ($value) {
                    $content[] = explode(",", $value);
                }
            }
            
            //插入数据
            foreach ($content as $key => $value) {

                $match_type = $value[0] ?? '';
                $keyword = $value[1] ?? '';
                $reply = $value[2] ?? '';

                $encoding = array('UTF-8', 'ASCII', 'GB2312', 'GBK');
                $keyword = mb_convert_encoding($keyword, "UTF-8", mb_detect_encoding($keyword, $encoding));
                $reply = mb_convert_encoding($reply, "UTF-8", mb_detect_encoding($reply, $encoding));
                
                if (!$keyword || !$reply) {
                    continue;
                }
            

                $fields = [
                    'match_type' => $match_type == '精确匹配' ? 1 : 0,
                    'keyword' => $keyword,
                    'reply' => [
                        [
                            'type' => 0,
                            'content' => $reply
                        ]
                    ],
                    'user_id' => self::$uid,
                    'account' => $params['account'],
                    'type' => $params['type'],
                ];

                // 是否存在
                $keyword = SvAccountKeyword::where('user_id', self::$uid)->where('account', $fields['account'])
                    ->where('type', $fields['type'])
                    ->where('keyword', $fields['keyword'])->findOrEmpty();
                if (!$keyword->isEmpty()) {
                    SvAccountKeyword::where('id', $keyword->id)->update($fields);
                } else {
                    SvAccountKeyword::create($fields);
                }
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
