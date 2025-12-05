<?php

namespace app\adminapi\logic\assistants;

use app\common\model\chat\Scene;
use app\common\logic\BaseLogic;
use app\common\service\FileService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\facade\Db;


/**
 * logic
 */
class SceneLogic extends BaseLogic
{
    /**
     * 添加
     * @param array $postData
     * @return bool
     * @author L
     * @data 2024-07-02 16:25:03
     */
    public static function add(array $postData): bool
    {
        try {

            if (isset($postData['logo'])) {
                $postData['logo'] = FileService::setFileUrl($postData['logo']);
            }

            self::$returnData = Scene::create($postData)->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 删除
     * @param array $data
     * @return bool
     * @author L
     * @data 2024-07-02 16:25:03
     */
    public static function delete(array $data): bool
    {
        try {
            if (is_string($data['id'])) {
                Scene::destroy(['id' => $data['id']]);
            } else {
                Scene::destroy($data['id']);
            }
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 编辑
     * @param array $postData
     * @return bool
     * @author L
     * @data 2024-07-02 16:25:03
     */
    public static function edit(array $postData): bool
    {
        try {
            $info = Scene::findOrEmpty($postData['id']);
            if ($info->isEmpty()) {
                throw new \Exception("信息异常");
            }

            if (isset($postData['logo'])) {
                $postData['logo'] = FileService::setFileUrl($postData['logo']);
            }

            self::$returnData = Scene::update($postData)->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 详情
     * @param int $id
     * @return bool
     * @author L
     * @data 2024-07-02 16:25:03
     */
    public static function detail(int $id): bool
    {
        try {
            $info = Scene::findOrEmpty($id);
            if ($info->isEmpty()) {
                throw new \Exception("信息异常");
            }
            $info->logo = FileService::getFileUrl($info['logo']);
            self::$returnData = $info->toArray();
            return true;
        } catch (\Exception $exception) {
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
        try {
            $assistantsInfo = Scene::findOrEmpty($id);
            if ($assistantsInfo->isEmpty()) {
                throw new \Exception("修改异常");
            }
            $assistantsInfo->status = 1 - $assistantsInfo->status;
            $assistantsInfo->save();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * @desc 导入
     * @param array $params
     * @return bool
     */
    public static function import()
    {
        try {
            $inputFileName = public_path().'static/file/template/scene.xlsx';
            if (!file_exists($inputFileName)) {
                throw new \Exception("文件不存在");
            }

            // 加载 Excel 文件
            $spreadsheet = IOFactory::load($inputFileName);

            // 获取活动工作表内容
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            Db::startTrans();          // 事务
            $inserted = 0;
            foreach ($rows as $k => $row) {
                if ($k == 1) continue; // 跳过表头
                $number = random_int(1, 50);
                $logo =   'static/images/assistant/assistant' . $number . '.png';
                if ($row['A'] == '' || $row['B'] == '') continue;
                $level1 = trim($row['A']);
                $level2 = trim($row['B']);

                if ($level1 === '' || $level2 === '') continue;
                /* ---------- 一级场景 ---------- */
                $parentId = Db::name('scene')
                    ->where('pid', 0)
                    ->where('name', $level1)
                    ->whereNull('delete_time')
                    ->value('id');

                if (!$parentId) {
                    $parentId = Db::name('scene')->insertGetId([
                        'pid'         => 0,
                        'name'        => $level1,
                        'logo'        => $logo,
                        'status'      => 1,
                        'description' => '',
                        'sort'        => 0,
                        'create_time' => time(),
                        'update_time' => time(),
                    ]);$inserted++;
                }

                /* ---------- 二级场景 ---------- */
                $exists = Db::name('scene')
                    ->where('pid', $parentId)
                    ->where('name', $level2)
                    ->whereNull('delete_time')
                    ->find();

                if (!$exists) {
                    Db::name('scene')->insert([
                        'pid'         => $parentId,
                        'name'        => $level2,
                        'logo'        => $logo,
                        'status'      => 1,
                        'description' => '',
                        'sort'        => 0,
                        'create_time' => time(),
                        'update_time' => time(),
                    ]);
                    $inserted++; }
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw new \Exception($e->getMessage());
        }
    }

    public static function check(){
        $exists = Db::name('scene')
            ->where('name', '利益相关方沟通')
            ->find();
        if ($exists){
            return false;
        }
        return true;
    }
}
