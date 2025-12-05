<?php


namespace app\adminapi\logic\hd;

use app\common\model\hd\HdImageCases;
use app\common\logic\BaseLogic;
use think\facade\Db;
use app\common\service\FileService;


/**
 * HdImageCaseLogic逻辑
 * Class HdImageCaseLogic
 * @package app\adminapi\logic\hd
 */
class HdImageCaseLogic extends BaseLogic
{
    /**
     * @desc 添加
     * @param array $params
     * @return bool
     * @date 2024/5/23 11:33
     * @author dagouzi
     */
    public static function add(array $params)
    {
        Db::startTrans();
        try {

            //模特图
            if ($params['case_type'] == 4) {

                $params['params'] = [];
            } else {

                foreach ($params['params']['images'] ?? [] as $key => $value) {

                    $params['params']['images'][$key] = FileService::setFileUrl($value);
                }
            }

            $params['params']       = json_encode($params['params'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $params['result_image'] = FileService::setFileUrl($params['result_image']);

            HdImageCases::create($params);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 编辑
     * @param array $params
     * @return bool
     * @date 2024/5/23 11:33
     * @author dagouzi
     */
    public static function edit(array $params): bool
    {
        Db::startTrans();
        try {

            //获取数据
            $case = HdImageCases::findOrEmpty($params['id']);

            if ($case->isEmpty()) {
                self::setError('案例数据不存在');
                return false;
            }

            //模特图
            if ($params['case_type'] == 4) {

                $params['params'] = [];
            } else {

                foreach ($params['params']['images'] ?? [] as $key => $value) {

                    $params['params']['images'][$key] = FileService::setFileUrl($value);
                }
            }

            $params['params'] = json_encode($params['params'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $case->case_type    = $params['case_type'];
            $case->status       = $params['status'];
            $case->params       = $params['params'];
            $case->result_image = FileService::setFileUrl($params['result_image']);
            $case->save();

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @desc 删除
     * @param array $data
     * @return bool
     * @date 2024/5/23 11:33
     * @author dagouzi
     */
    public static function delete(array $data): bool
    {
        try {
            if (is_string($data['id'])) {
                HdImageCases::destroy(['id' => $data['id']]);
            } else {
                HdImageCases::destroy($data['id']);
            }
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * @desc 获取详情
     * @param int $id
     * @return array
     * @date 2024/5/23 11:33
     * @author dagouzi
     */
    public static function detail(int $id): array
    {
        return HdImageCases::findOrEmpty($id)->toArray();
    }


    /**
     * @desc 修改状态
     * @param int $id
     * @param int $status
     * @return bool
     * @date 2024/12/25 15:50
     * @author dagouzi
     */
    public static function changeStatus(int $id, int $status): bool
    {
        //获取数据
        $case = HdImageCases::findOrEmpty($id);

        if ($case->isEmpty()) {
            self::setError('案例数据不存在');
            return false;
        }

        $case->status = $status;
        $case->save();

        self::$returnData = $case->toArray();
        return true;
    }
}
