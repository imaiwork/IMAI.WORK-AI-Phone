<?php


namespace app\adminapi\logic\oem;

use app\common\logic\BaseLogic;
use app\common\model\oem\Oem;
use app\common\model\user\User;
use app\common\service\FileService;

/**
 * oem管理逻辑
 * Class OemLogic
 * @package app\adminapi\logic\oem
 */
class OemLogic extends BaseLogic
{

    public static function getOemInfo()
    {
        $result =  \app\common\service\ToolsService::Auth()->checkby();
        $result['authnum'] = (int)$result['authnum'];
        $result['useauthnum'] = Oem::count();
        $result['balance'] = $result['authnum'] - $result['useauthnum'];
        unset($result['bystatus'], $result['byname']);
        return $result;
    }


    /**
     * @notes  添加oem
     * @param array $params
     */
    public static function add(array $params)
    {
        try {
            //检查当前域名是否有配置oem权限
            self::checkOem();

            $find = Oem::where('user_id', $params['user_id'])->findOrEmpty();
            if (!$find->isEmpty()) {
                self::setError('该用户已绑定oem');
                return false;
            }

            $find = Oem::where('domain', $params['domain'])->findOrEmpty();
            if (!$find->isEmpty()) {
                self::setError('该域名已绑定oem');
                return false;
            }
            $params['auth_time'] = date('Y-m-d H:i:s', time());
            $params['status'] = $params['status'] ?? 1;
            $params['name'] = $params['name'] ?? 1;
            $params['update_time'] = time();
            $params['logo_url'] = $params['logo_url'] ? FileService::setFileUrl($params['logo_url']) : '';
            $params['site_logo'] = $params['site_logo'] ? FileService::setFileUrl($params['site_logo']) : '';
            $params['username'] = User::where('id', $params['user_id'])->value('nickname');
            
            //更新oem数量
            $num = Oem::count();
            $result = \app\common\service\ToolsService::Auth()->updateNum(($num + 1));
            if($result['code'] != 10000){
                self::setError($result['msg']);
                return false;
            }
            Oem::create($params);
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
        return true;
    }


    /**
     * @notes  编辑oem
     * @param array $params
     * @return bool
     */
    public static function edit(array $params): bool
    {
        try {
            //检查当前域名是否有配置oem权限
            self::checkOem(false);

            $oemRow = Oem::where('id', $params['id'])->findOrEmpty();
            if ($oemRow->isEmpty()) {
                self::setError('无效参数id');
                return false;
            }

            $find = Oem::where('user_id', $params['user_id'])->where('id', '<>', $params['id'])->findOrEmpty();
            if (!$find->isEmpty()) {
                self::setError('该用户已绑定oem');
                return false;
            }
            $find = Oem::where('domain', $params['domain'])->where('id', '<>', $params['id'])->findOrEmpty();
            if (!$find->isEmpty()) {
                self::setError('该域名已绑定oem');
                return false;
            }
            $params['update_time'] = time();
            $params['logo_url'] = $params['logo_url'] ? FileService::setFileUrl($params['logo_url']) : '';
            $params['site_logo'] = $params['site_logo'] ? FileService::setFileUrl($params['site_logo']) : '';
            $params['username'] = User::where('id', $params['user_id'])->value('nickname');
            Oem::update($params);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes  删除oem
     * @param array $params
     */
    public static function delete(array $params)
    {
        try {
            if (is_string($params['id'])) {
                Oem::destroy(['id' => $params['id']]);
            } else {
                Oem::destroy($params['id']);
            }
             //更新oem数量
            $num = Oem::count();
            $result = \app\common\service\ToolsService::Auth()->updateNum($num);
            if($result['code'] != 10000){
                self::setError($result['msg']);
                return false;
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes  查看oem详情  
     * @param $params
     * @return array
     */
    public static function detail($params): array
    {
        $result = Oem::findOrEmpty($params['id'])->toArray();
        $result['logo_url'] = FileService::getFileUrl($result['logo_url']);
        $result['site_logo'] = is_null($result['site_logo']) ? '' : FileService::getFileUrl($result['site_logo']);
        return $result;
    }

    /**
     * @notes  更改oem状态
     * @param array $params
     * @return bool
     */
    public static function changeStatus(array $params)
    {
        $find = Oem::findOrEmpty($params['id']);
        if ($find->isEmpty()) {
            self::setError('站点域名数据不存在');
            return false;
        }
        $find->status = 1 - $find->status;
        $find->update_time = time();
        $find->save();

        return true;
    }

    private static function checkOem($isCheck = true){
        $result =  \app\common\service\ToolsService::Auth()->checkby();
        if(!isset($result['authnum'])){
            throw new \Exception('当前站点域名未授权配置OEM');
            return false;
        }
        if((int)$result['authnum'] === 0){
            throw new \Exception('当前站点域名未授权配置OEM');

            return false;
        }
        $useauthnum = Oem::count();;
        if((int)$useauthnum === (int)$result['authnum'] && $isCheck){
            throw new \Exception('当前站点域名配置OEM余额不足');
            return false;
        }
    }

}
