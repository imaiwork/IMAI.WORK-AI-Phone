<?php


namespace app\adminapi\controller\setting;


use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\setting\StorageLogic;
use app\adminapi\validate\setting\StorageValidate;
use think\response\Json;


/**
 * 存储设置控制器
 * Class StorageController
 * @package app\adminapi\controller\setting\shop
 */
class StorageController extends BaseAdminController
{

    /**
     * @notes 获取存储引擎列表
     * @return Json
     * @author 段誉
     * @date 2022/4/20 16:13
     */
    public function lists()
    {
        return $this->data(StorageLogic::lists());
    }


    /**
     * @notes 存储配置信息
     * @return Json
     * @author 段誉
     * @date 2022/4/20 16:19
     */
    public function detail()
    {
        $param = (new StorageValidate())->get()->goCheck('detail');
        return $this->data(StorageLogic::detail($param));
    }


    /**
     * @notes 设置存储参数
     * @return Json
     * @author 段誉
     * @date 2022/4/20 16:19
     */
    public function setup()
    {
        $params = (new StorageValidate())->post()->goCheck('setup');
        $result = StorageLogic::setup($params);
        if (true === $result) {
            return $this->success('配置成功', [], 1, 1);
        }
        // 本地关闭时的提示仍按成功返回；其余非 true 视为配置失败
        if ($result === '默认开启本地存储') {
            return $this->success($result, [], 1, 1);
        }
        return $this->fail((string)$result);
    }


    /**
     * @notes 切换存储引擎
     * @return Json
     * @author 段誉
     * @date 2022/4/20 16:19
     */
    public function change()
    {
        $params = (new StorageValidate())->post()->goCheck('change');
        StorageLogic::change($params);
        return $this->success('切换成功', [], 1, 1);
    }


    /**
     * @notes  迁移
     * @return Json
     * @author 段誉
     * @date 2022/4/20 16:19
     */
    public function migration()
    {
        $params = (new StorageValidate())->post()->goCheck('migration');
        $result = StorageLogic::migration($params);
        if (true === $result) {
            return $this->success('配置成功', [], 1, 1);
        }
        return $this->fail($result);
    }
}
