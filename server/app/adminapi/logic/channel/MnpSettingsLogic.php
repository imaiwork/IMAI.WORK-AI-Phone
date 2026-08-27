<?php


namespace app\adminapi\logic\channel;

use app\common\logic\BaseLogic;
use app\common\model\decorate\DecorateTabbar;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\wechat\MnpCiService;
use Exception;

/**
 * 小程序设置逻辑
 * Class MnpSettingsLogic
 * @package app\adminapi\logic\channel
 */
class MnpSettingsLogic extends BaseLogic
{
    /**
     * @notes 获取小程序配置
     * @return array
     * @author ljj
     * @date 2022/2/16 9:38 上午
     */
    public function getConfig()
    {
        $domainName = $_SERVER['SERVER_NAME'];
        $qrCode = ConfigService::get('mnp_setting', 'qr_code', '');
        $qrCode = empty($qrCode) ? $qrCode : FileService::getFileUrl($qrCode);
        $config = [
            'name'                 => ConfigService::get('mnp_setting', 'name', ''),
            'original_id'          => ConfigService::get('mnp_setting', 'original_id', ''),
            'qr_code'              => $qrCode,
            'app_id'               => ConfigService::get('mnp_setting', 'app_id', ''),
            'app_secret'           => ConfigService::get('mnp_setting', 'app_secret', ''),
            'private_key'          => ConfigService::get('mnp_setting', 'private_key', ''),
            'app_version'          => ConfigService::get('mnp_setting', 'app_version', '2.0.0'),
            'request_domain'       => 'https://' . $domainName,
            'socket_domain'        => 'wss://' . $domainName,
            'upload_file_domain'   => 'https://' . $domainName,
            'download_file_domain' => 'https://' . $domainName,
            'udp_domain'           => 'udp://' . $domainName,
            'business_domain'      => $domainName,
        ];

        return $config;
    }

    /**
     * @notes 设置小程序配置
     * @param $params
     * @author ljj
     * @date 2022/2/16 9:51 上午
     */
    public function setConfig($params)
    {
        $qrCode = isset($params['qr_code']) ? FileService::setFileUrl($params['qr_code']) : '';

        ConfigService::set('mnp_setting', 'name', $params['name'] ?? '');
        ConfigService::set('mnp_setting', 'original_id', $params['original_id'] ?? '');
        ConfigService::set('mnp_setting', 'qr_code', $qrCode);
        ConfigService::set('mnp_setting', 'app_id', $params['app_id']);
        ConfigService::set('mnp_setting', 'app_secret', $params['app_secret']);
        ConfigService::set('mnp_setting', 'app_version', $params['app_secret']);

        if (!empty($params['private_key'])) {
            $saveDir = '../extend/miniprogram-ci/';
            if (!file_exists($saveDir)) {
                mkdir($saveDir, 0775, true);
            }
            //保存文件
            $savePath = $saveDir . 'private.' . $params['app_id'] . '.key';
            $f = fopen($savePath, 'w');
            fwrite($f, $params['private_key']);
            fclose($f);

            ConfigService::set('mnp_setting', 'private_key', $params['private_key']);
        }else{
            ConfigService::set('mnp_setting', 'private_key', "");
        }

        $path = 'ai_modules/ladder_player/pages/index/index';
        $path = public_path() . 'uploads/images/' . md5($path) . '.png';
        if (file_exists($path)) {
            @unlink($path);
        }
    }

    /**
     * @notes 上传小程序
     * @param $params
     * @return bool|array
     * @author mjf
     * @date 2025/1/8 17:33
     */
    public function uploadMnp($params): bool|array
    {
        try {
            //校验是否已安装miniprogram-ci工具
            if (!file_exists('../extend/miniprogram-ci/node_modules/miniprogram-ci')) {
                throw new Exception('请先安装miniprogram-ci工具');
            }

            if (!file_exists('../extend/miniprogram-ci/mp-weixin')) {
                throw new Exception('请先上传小程序代码文件');
            }

            $appid = ConfigService::get('mnp_setting', 'app_id', '');
            if (!file_exists('../extend/miniprogram-ci/private.'.$appid.'.key')) {
                throw new Exception('请先设置小程序上传私钥');
            }

            //更换小程序域名
            $baseUrl = '../extend/miniprogram-ci/mp-weixin/config/index.js';
            $baseUrlData = file_get_contents($baseUrl);
            $domain = request()->domain(true) .'/';

            $pattern = '/baseUrl:[a-zA-Z]/';
            if (preg_match($pattern, $baseUrlData)) {
                $replacement = 'baseUrl:"'.$domain.'"';
                $newContent = preg_replace($pattern, $replacement, $baseUrlData);
                file_put_contents($baseUrl, $newContent);
            }else{
                $pattern = '/baseUrl:"(https?:\/\/[^"]+\/?)"/';
                if (preg_match($pattern, $baseUrlData, $matches)) {
                    // 获取文件内域名
                    $currentUrl = $matches[1];
                    if ($currentUrl !== $domain) {
                        $replacement = 'baseUrl:"' . $domain . '"';
                        $newContent = preg_replace($pattern, $replacement, $baseUrlData);
                        file_put_contents($baseUrl, $newContent);
                    }
                }
            }

            // 同步 appid 到小程序项目配置
            $projectConfigPath = '../extend/miniprogram-ci/mp-weixin/project.config.json';
            if (!file_exists($projectConfigPath)) {
                throw new Exception('小程序项目配置文件不存在：project.config.json');
            }
            $projectConfigData = file_get_contents($projectConfigPath);
            $projectConfig = json_decode($projectConfigData, true);
            if (!is_array($projectConfig)) {
                throw new Exception('小程序项目配置文件解析失败：project.config.json');
            }
            if (($projectConfig['appid'] ?? '') !== $appid) {
                $projectConfig['appid'] = $appid;
                $encoded = json_encode($projectConfig, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                if ($encoded === false) {
                    throw new Exception('小程序项目配置文件写入失败：project.config.json');
                }
                file_put_contents($projectConfigPath, $encoded . PHP_EOL);
            }

            // 同步底部导航跳转小程序 appid 白名单到 app.json
            $this->syncNavigateToMiniProgramAppIdList();

            //上传小程序代码
            $data = [
                'version' => $params['upload_version'] ?? ConfigService::get('mnp_setting', 'app_version', '2.0.0'),
                'desc'    => $params['upload_desc'] ?? '',
                'appid'   => $appid,
            ];
            $json_data = json_encode($data);
            $command = MnpCiService::buildUploadCommand('../extend/miniprogram-ci/upload.js', $json_data);
            $output = null;
            $retval = null;
            exec($command, $output, $retval);
            $outputText = implode(PHP_EOL, $output ?? []);

            if ($retval) {
                $result = ['code' => 0, 'msg' => $outputText, 'retval'=>$retval];
            }else{
                if (!str_contains($outputText, 'UPLOAD_SUCCESS')) {
                    $result = [
                        'code'       => 0,
                        'msg'        => '未检测到微信上传成功标记，请检查上传脚本是否完整执行。' . PHP_EOL . $outputText,
                        'retval'     => $retval,
                        'appid'      => $appid,
                        'version'    => $data['version'],
                    ];
                    return $result;
                }

                if (!empty($params['upload_version'])){
                    ConfigService::set('mnp_setting', 'app_version', $params['upload_version']);
                }
                $result = [
                    'code'       => 1,
                    'msg'        => '上传成功',
                    'appid'      => $appid,
                    'version'    => $data['version'],
                    'upload_log' => $this->getUploadSummaryLog($output ?? []),
                ];
            }
            return $result;
        } catch (Exception $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }

    private function getUploadSummaryLog(array $output): string
    {
        $summary = array_values(array_filter($output, static function ($line) {
            return str_contains($line, '[upload]')
                || str_contains($line, 'UPLOAD_SUCCESS')
                || str_contains($line, 'upload by')
                || str_contains($line, 'request url');
        }));

        return implode(PHP_EOL, $summary ?: array_slice($output, -30));
    }

    /**
     * @notes 从底部导航收集 miniapp 的 appid，写入 app.json 的 navigateToMiniProgramAppIdList
     * @throws Exception
     */
    private function syncNavigateToMiniProgramAppIdList(): void
    {
        $appJsonPath = '../extend/miniprogram-ci/mp-weixin/app.json';
        if (!file_exists($appJsonPath)) {
            throw new Exception('小程序配置文件不存在：app.json');
        }

        $appJsonData = file_get_contents($appJsonPath);
        $appJson = json_decode($appJsonData, true);
        if (!is_array($appJson)) {
            throw new Exception('小程序配置文件解析失败：app.json');
        }

        $miniAppIds = [];
        $tabbarList = DecorateTabbar::getTabbarLists();
        foreach ($tabbarList as $item) {
            $link = $item['link'] ?? [];
            if (!is_array($link)) {
                continue;
            }
            if (($link['type'] ?? '') !== 'miniapp') {
                continue;
            }
            $targetAppId = trim((string)($link['appid'] ?? ''));
            if ($targetAppId === '') {
                continue;
            }
            $miniAppIds[] = $targetAppId;
        }

        // 去重，微信白名单最多 10 个
        $miniAppIds = array_values(array_unique($miniAppIds));
        $miniAppIds = array_slice($miniAppIds, 0, 10);

        $appJson['navigateToMiniProgramAppIdList'] = $miniAppIds;
        // PHP json_decode 会把空对象 {} 变成 []，写回后 miniprogram-ci 校验失败，需强制保持对象
        $appJson['plugins'] = $this->toJsonObject($appJson['plugins'] ?? []);
        $appJson['usingComponents'] = $this->toJsonObject($appJson['usingComponents'] ?? []);

        $encoded = json_encode($appJson, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new Exception('小程序配置文件写入失败：app.json');
        }
        file_put_contents($appJsonPath, $encoded . PHP_EOL);
    }

    /**
     * @notes 将 assoc 数组转为 JSON 对象，避免空数组被编码成 []
     */
    private function toJsonObject(mixed $value): object
    {
        if (is_object($value)) {
            return $value;
        }
        if (!is_array($value) || $value === []) {
            return new \stdClass();
        }

        return (object)$value;
    }

    /**
     * @notes 获取小程序本地版本号
     * @return array
     * @author Rick
     * @date 2025/6/23 17:36
     */
    public function getMnpLocalVersion(): array
    {
        $lastVersion = ConfigService::get('mnp_setting', 'app_version', '2.0.0');
        $version = $this->incrementVersion($lastVersion);
        return ['version' => $version];
    }

    /**
     * @notes 获取小程序分享配置
     * @return array
     * @author ljj
     * @date 2022/2/16 9:38 上午
     */
    public function getShareConfig()
    {
        $image = ConfigService::get('mnp_setting', 'share_image', '');
        $image = empty($image) ? $image : FileService::getFileUrl($image);
        $config = [
            'share_title'   => ConfigService::get('mnp_setting', 'share_title', ''),
            'share_desc'    => ConfigService::get('mnp_setting', 'share_desc', ''),
            'share_image' => $image,
        ];

        return $config;
    }

    /**
     * @notes 设置小程序分享配置
     * @param $params
     * @author ljj
     * @date 2022/2/16 9:51 上午
     */
    public function setShareConfig($params)
    {
        $image = isset($params['share_image']) ? FileService::setFileUrl($params['share_image']) : '';
        ConfigService::set('mnp_setting', 'share_image', $image);
        ConfigService::set('mnp_setting', 'share_title', $params['share_title']);
        ConfigService::set('mnp_setting', 'share_desc', $params['share_desc']);
    }

     /**
     * 自动递增版本号，遵循语义化版本规则，只保留个位
     *
     * @param string $currentVersion 当前版本号，格式为"x.y.z"
     * @return string 递增后的新版本号
     */
    private function incrementVersion(string $currentVersion): string
    {
        $parts = explode('.', $currentVersion);
        // 确保版本号有三个部分
        if (count($parts) !== 3) {
            return "2.0.0";
        }
        $major = (int) $parts[0];
        $minor = (int) $parts[1];
        $patch = (int) $parts[2];
        $patch++;
        // 处理进位
        if ($patch >= 10) {
            $patch = 0;
            $minor++;
            // 处理次版本号进位
            if ($minor >= 10) {
                $minor = 0;
                $major++;
            }
        }
        return "$major.$minor.$patch";
    }
}
