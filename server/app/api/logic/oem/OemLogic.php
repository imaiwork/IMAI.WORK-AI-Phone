<?php

namespace app\api\logic\oem;

use app\api\logic\ApiLogic;
use think\facade\Request;

/**
 * OemLogic
 * @desc oem
 */
class OemLogic extends ApiLogic
{
    public static function check()
    {
        $domain = \app\api\logic\TeamLogic::normalizeDomain((string)Request::domain());
        // 小程序走 API 主域时靠 appid 解析团队 OEM
        $appid = (string)Request::param('appid', '');
        if ($appid === '') {
            $appid = (string)Request::header('appid', '');
        }

        // 统一入口:团队OEM优先(已开通+启用)→已解散关站→旧版OEM(iw_oem)回落。
        $tenant = \app\api\logic\TeamLogic::resolveTenant($domain, $appid);

        // 已解散 OEM 站点:明确关站,禁止回落主站
        if (!empty($tenant['site_closed'])) {
            self::$returnData = [
                'is_oem' => 0,
                'site_closed' => 1,
                'close_reason' => (string)($tenant['close_reason'] ?? 'team_disbanded'),
                'message' => (string)($tenant['message'] ?? '该站点已关闭'),
                'domain' => $domain,
                'logo_url' => '',
                'site_logo' => '',
                'name' => '',
                'admin_qr' => '',
                'icp_number' => '',
                'company_name' => '',
            ];
            return true;
        }

        if (!empty($tenant['is_team']) || !empty($tenant['is_oem'])) {
            $logo = (string)($tenant['web_logo'] ?? '');
            self::$returnData = [
                'is_oem'    => 1,
                'site_closed' => 0,
                'domain'    => $domain,
                'name'      => (string)($tenant['name'] ?? ''),
                'logo_url'  => $logo,
                'site_logo' => (string)($tenant['pc_logo'] ?? '') ?: $logo,
                'admin_qr'  => (string)($tenant['admin_qr'] ?? ''),
                'icp_number' => (string)($tenant['icp_number'] ?? ''),
                'company_name' => (string)($tenant['company_name'] ?? ''),
            ];
        } else {
            self::$returnData = [
                'is_oem' => 0,
                'site_closed' => 0,
                'domain' => $domain,
                'logo_url' => '',
                'site_logo' => '',
                'name' => '',
                'admin_qr' => '',
                'icp_number' => '',
                'company_name' => '',
            ];
        }

        return true;
    }
}
