<?php

namespace app\adminapi\controller;


use app\common\cache\ExportCache;
use app\common\service\JsonService;

class DownloadController extends BaseAdminController
{

    public array $notNeedLogin = ['export'];

    /**
     * @notes 导出文件
     * @return \think\response\File|\think\response\Json
     * @author 段誉
     * @date 2022/11/24 16:10
     */
    public function export()
    {
        //获取文件缓存的key
        $fileKey = request()->get('file');

        //通过文件缓存的key获取文件储存的路径
        $exportCache = new ExportCache();
        $fileInfo = $exportCache->getFile($fileKey);

        if (empty($fileInfo)) {
            return JsonService::fail('下载文件不存在');
        }

        //下载前删除缓存
        $exportCache->delete($fileKey);
        $file = $fileInfo['src'] . $fileInfo['name'];
        header('Content-Disposition: attachment; filename=' .$fileInfo['name']);
        header('Content-type: application/octet-stream');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;

        //return download($fileInfo['src'] . $fileInfo['name'], $fileInfo['name']);
    }
}
