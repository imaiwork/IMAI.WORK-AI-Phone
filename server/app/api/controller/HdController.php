<?php

namespace app\api\controller;


use app\api\lists\hd\HdImageCaseLists;
use app\api\lists\hd\HdLists;
use app\api\logic\HdCueLogic;
use app\api\logic\HdLogic;
use app\api\validate\HdValidate;
use think\exception\HttpResponseException;
use think\response\Json;

class HdController extends BaseApiController
{
    public array $notNeedLogin = ['cueImage'];
    /**
     * @notes 文件
     * @author 段誉
     * @date 2022/9/20 15:30
     */
    public function lists()
    {
        return $this->dataLists(new HdLists());
    }

    public function deleteImage()
    {
        $params = $this->request->post();
        return HdLogic::deleteImage($params) ? $this->success('ok') : $this->fail(HdLogic::getError());
    }

    public function categorys()
    {
        $data = [
            ["category_en" => "Studio", "category_zh" => "展台"],
            ["category_en" => "Outdoor", "category_zh" => "户外"],
            ["category_en" => "Home", "category_zh" => "居家"],
            ["category_en" => "Fashion", "category_zh" => "时尚"],
            ["category_en" => "Top view", "category_zh" => "俯视"]
        ];
        return $this->data($data);
    }

    /**
     * @desc 获取模板列表
     * @return Json
     * @date 2024/7/5 9:28
     * @author dagouzi
     */
    public function templates(): Json
    {
        $params = $this->request->get();
        $result = HdLogic::templates($params);
        if ($result) {
            return $this->data(HdLogic::getReturnData());
        }
        return $this->data(HdLogic::getError());
    }


    /**
     * @desc 提交商品图图生图任务
     * @return Json
     * @date 2024/7/5 14:38
     * @author dagouzi
     */
    public function segmentImage()
    {
        $params = $this->request->post();
        $result = HdLogic::segmentImage($params);
        if ($result) {
            return $this->data(HdLogic::getReturnData());
        }

        return $this->data(HdLogic::getError());
    }


    /**
     * @desc 提交ai试衣生图任务
     * @return Json
     * @date 2024/7/5 14:38
     * @author dagouzi
     */
    public function vton()
    {
        $params = $this->request->post();
        $result = HdLogic::vton($params);
        if ($result) {
            return $this->data(HdLogic::getReturnData());
        }
        return $this->data(HdLogic::getError());
    }

    /**
     * @desc 获取案例列表
     * @return Json
     * @date 2024/7/5 14:38
     * @author dagouzi
     */
    public function caseLists()
    {
        return $this->dataLists(new HdImageCaseLists());
    }

    /**
     * @desc 添加模特案例
     * @return Json
     * @date 2024/7/5 14:38
     * @author dagouzi
     */
    public function addModelCase()
    {
        $params = $this->request->post();
        $result = HdLogic::addModelCase($params);
        return $this->success('添加成功');
    }

    /**
     * @desc 删除模特案例
     * @return Json
     * @date 2024/7/5 14:38
     * @author dagouzi
     */
    public function delModelCase()
    {
        $params = $this->request->post();
        $result = HdLogic::delModelCase($params);
        if ($result){
            return $this->success('删除成功');
        }
        return $this->pc_fail('无法删除系统模特');
    }

    /**
     * @desc 获取任务状态
     * @return Json
     * @date 2024/7/20 10:47
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author dagouzi
     */
    public function getTaskStatus()
    {
        $params = $this->request->post();
        $result = HdLogic::getTaskStatus($params);
        if ($result) {
            return $this->data(HdLogic::getReturnData());
        }
        return $this->data(HdLogic::getError());
    }

    /**
     * @desc 提交文生图任务
     * @return Json
     * @date 2024/7/20 11:09
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author dagouzi
     */
    public function txt2img()
    {
        $params = $this->request->post();
        $result = HdLogic::txt2img($params);
        if ($result) {
            return $this->data(HdLogic::getReturnData());
        }
        return $this->data(HdLogic::getError());
    }

    /**
     * @desc 提交图生图任务
     * @return Json
     * @date 2024/7/20 11:09
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author dagouzi
     */
    public function img2img()
    {
        $params = $this->request->post();
        $result = HdLogic::img2img($params);
        if ($result) {
            return $this->data(HdLogic::getReturnData());
        }
        return $this->data(HdLogic::getError());
    }

    /**
     * @desc 提示词
     * @return Json
     * @date 2024/7/26 17:34
     * @author dagouzi
     */
    public function cueWord()
    {
        $result = HdCueLogic::word();
        if ($result) {
            return $this->data(HdCueLogic::getReturnData());
        }
        return $this->data(HdCueLogic::getError());
    }

    /**
     * @desc 图片提示词
     * @return Json
     * @date 2024/7/26 17:34
     * @author dagouzi
     */
    public function cueImage()
    {
        $params = $this->request->post();
        $result = HdCueLogic::image($params);
        if ($result) {
            return $this->data(HdCueLogic::getReturnData());
        }
        return $this->data(HdCueLogic::getError());
    }

    /**
     * @desc 图片提示词分类
     * @return Json
     * @date 2024/7/27 9:26
     * @author dagouzi
     */
    public function cueImageCategory()
    {
        $result = HdCueLogic::imageCategory();
        if ($result) {
            return $this->data(HdCueLogic::getReturnData());
        }
        return $this->data(HdCueLogic::getError());
    }

    /**
     * @desc 定时任务
     * @return true
     * @date 2024/7/5 17:54
     * @throws \Exception
     * @author dagouzi
     */
    public function cron()
    {
        HdLogic::cron();
        return true;
    }


    /**
     * @desc 提交文生图任务
     * @return Json
     * @date 2024/7/20 11:09
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author dagouzi
     */
    public function txt2volcimg()
    {
        try {
            $params = $this->request->post();

            if (isset($params['model']) && (int)$params['model'] === 3) {
                $result = HdLogic::text2img($params);
            } else {
                $result = HdLogic::txt2volcimg($params);

            }
            if ($result) {
                return $this->data(HdLogic::getReturnData());
            }
            return $this->data([HdLogic::getError()]);
        } catch (HttpResponseException $e) {
            return $this->data($e->getResponse()->getData() ?? []);
        }
    }


    public function img2volcimg()
    {
        try {
            $params = (new HdValidate())->post()->goCheck('img2img');
            $result = HdLogic::img2volcimg($params);
            if ($result) {
                return $this->data(HdLogic::getReturnData());
            }
            return $this->data([HdLogic::getError()]);
        } catch (HttpResponseException $e) {
            return $this->data($e->getResponse()->getData() ?? []);
        }
    }
}
