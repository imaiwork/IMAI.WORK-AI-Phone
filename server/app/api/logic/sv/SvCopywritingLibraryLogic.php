<?php

namespace app\api\logic\sv;

use app\common\model\sv\SvCopywritingLibrary;

/**
 * CopywritingLibraryLogic
 * 文案库逻辑处理
 */
class SvCopywritingLibraryLogic extends SvBaseLogic
{
    /**
     * 添加文案库
     */
    public static function add(array $params)
    {
        try {
            $params['user_id'] = self::$uid;

            $item = [
                '1'=>'内容文案',
                '2'=>'口播文案',
                '3'=>'口播混剪文案'
            ];
            $params['name'] = $item[$params['copywriting_type']] . ' '. date('Y-m-d H:i', time());
            $jsonFields = ['title', 'described', 'oral_copy', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    if (is_array($params[$field])) {
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $params[$field] = json_encode($decoded, JSON_UNESCAPED_UNICODE);
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                } else if (isset($params[$field])) {
                    $params[$field] = json_encode([]);
                }
            }
            $library = SvCopywritingLibrary::create($params);
            self::$returnData = $library->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 获取文案库详情
     */
    public static function detail(array $params)
    {
        try {
            $library = SvCopywritingLibrary::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if (!$library) {
                self::setError('文案库不存在');
                return false;
            }
            $data = $library->toArray();
            $jsonFields = ['title', 'described', 'oral_copy', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($data[$field])) {
                    $data[$field] = json_decode($data[$field], true);
                } else {
                    $data[$field] = [];
                }
            }
            self::$returnData = $data;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 更新文案库
     */
    public static function update(array $params)
    {
        try {
            $library = SvCopywritingLibrary::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
            if (!$library) {
                self::setError('文案库不存在');
                return false;
            }
            $jsonFields = ['title', 'described', 'oral_copy', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    if (is_array($params[$field])) {
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $params[$field] = json_encode($decoded, JSON_UNESCAPED_UNICODE);
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                } else if (isset($params[$field])) {
                    $params[$field] = json_encode([]);
                }
            }

            SvCopywritingLibrary::where('id', $params['id'])->update($params);
            self::$returnData = SvCopywritingLibrary::find($params['id'])->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 删除文案库
     */
    public static function del($id)
    {
        try {
            if (is_string($id)) {
                SvCopywritingLibrary::destroy(['id' => $id, 'user_id' => self::$uid]);
            } else {
                SvCopywritingLibrary::whereIn('id', $id)->where('user_id', self::$uid)->select()->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * AI 生成文案：统一走中台 /api/coze/matrixtext（一次调用覆盖原 title/subtitle 或口播文案）
     */
    public static function addAi($params)
    {
        try {
            $params['user_id'] = self::$uid;

            $ok = ToolsLogic::getMatrixCopywriting([
                'user_id' => self::$uid,
                'keywords' => $params['keyword'],
                'number' => (int)$params['total_num'],
            ]);
            if (!$ok) {
                self::setError(ToolsLogic::getError() ?: '生成失败');
                return false;
            }

            $list = ToolsLogic::getReturnData();
            if (!is_array($list) || count($list) === 0) {
                self::setError('生成失败');
                return false;
            }

            $title = [];
            $described = [];
            $oralCopy = [];

            foreach ($list as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $itemTitle = (string)($item['title'] ?? '');
                $itemContent = (string)($item['content'] ?? '');

                if ((int)$params['copywriting_type'] === 1) {
                    $title[] = ['content' => $itemTitle];
                    $described[] = ['content' => $itemContent];
                } else {
                    $oralCopy[] = ['content' => $itemContent !== '' ? $itemContent : $itemTitle];
                }
            }

            self::$returnData = [
                'title' => $title,
                'described' => $described,
                'oral_copy' => $oralCopy,
                'content' => $params['keyword'],
                'user_id' => self::$uid,
                'copywriting_type' => $params['copywriting_type'],
                'status' => ((int)($params['channel'] ?? 0) === 2) ? 2 : 0,
            ];
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
