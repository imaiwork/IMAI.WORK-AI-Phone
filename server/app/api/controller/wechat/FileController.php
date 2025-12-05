<?php


namespace app\api\controller\wechat;

use app\api\controller\BaseApiController;
use app\api\logic\wechat\FileLogic;
use app\api\lists\wechat\FileGroupLists;
use app\api\lists\wechat\FileLists;

/**
 * FileGroupController
 * @desc 素材库文件分组
 * @author Qasim
 */
class FileController extends BaseApiController
{

    public array $notNeedLogin = [];


    /**
     * @desc 分组列表
     */
    public function groupLists()
    {
        return $this->dataLists(new FileGroupLists());
    }

    /**
     * @desc 文件列表
     */
    public function fileLists()
    {
        return $this->dataLists(new FileLists());
    }

    /**
     * @desc 添加分组名称
     */
    public function addGroup()
    {
        $name = $this->request->post('group_name', '');

        if (empty($name))
        {
            return $this->fail('分组名称不能为空');
        }

        $result = FileLogic::addGroup($name);
        if ($result)
        {
            return $this->success(data: FileLogic::getReturnData());
        }
        return $this->fail(FileLogic::getError());
    }

    /**
     * @desc 更新分组名称
     */
    public function updateGroup()
    {
        $id = $this->request->post('id', default: 0);
        $name = $this->request->post('group_name', default: '');

        $result = FileLogic::updateGroup($id, $name);
        if ($result)
        {
            return $this->success(data: FileLogic::getReturnData());
        }
        return $this->fail(FileLogic::getError());
    }

    /**
     * @desc 删除分组名称
     */
    public function deleteGroup()
    {
        $id = $this->request->post('id', 0);

        $result = FileLogic::deleteGroup($id);
        if ($result)
        {
            return $this->success();
        }
        return $this->fail(FileLogic::getError());
    }


    /**
     * @desc 添加文件
     */
    public function add()
    {
        $params = $this->request->post();

        $result = FileLogic::addFile($params);
        if ($result)
        {
            return $this->success(data: FileLogic::getReturnData());
        }
        return $this->fail(FileLogic::getError());
    }

    /**
     * @desc 更新文件
     */
    public function update()
    {
        $params = $this->request->post();

        $result = FileLogic::updateFile($params);
        if ($result)
        {
            return $this->success(data: FileLogic::getReturnData());
        }
        return $this->fail(FileLogic::getError());
    }

    /**
     * @desc 删除文件
     */
    public function delete()
    {
        $ids = $this->request->post('ids', []);

        $result = FileLogic::deleteFile($ids);
        if ($result)
        {
            return $this->success();
        }
        return $this->fail(FileLogic::getError());
    }

    /**
     * @desc 获取文件
     */
    public function info()
    {
        $id = $this->request->get('id', 0);

        $result = FileLogic::fileInfo($id);
        if ($result)
        {
            return $this->success(data: FileLogic::getReturnData());
        }
        return $this->fail(FileLogic::getError());
    }
}
