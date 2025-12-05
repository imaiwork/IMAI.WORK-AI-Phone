<?php


namespace app\adminapi\logic\setting\web;


use app\common\logic\BaseLogic;
use app\common\service\ConfigService;
use app\common\service\FileService;


/**
 * 网站设置
 * Class WebSettingLogic
 * @package app\adminapi\logic\setting
 */
class WebSettingLogic extends BaseLogic
{

    /**
     * @notes 获取网站信息
     * @return array
     * @author 段誉
     * @date 2021/12/28 15:43
     */
    public static function getWebsiteInfo(): array
    {
        $image = ConfigService::get('website', 'share_image', '');
        $image = empty($image) ? $image : FileService::getFileUrl($image);

        return [
            'name' => ConfigService::get('website', 'name'),
            'web_favicon' => FileService::getFileUrl(ConfigService::get('website', 'web_favicon')),
            'web_logo' => FileService::getFileUrl(ConfigService::get('website', 'web_logo')),
            'login_image' => FileService::getFileUrl(ConfigService::get('website', 'login_image')),
            'shop_name' => ConfigService::get('website', 'shop_name'),
            'shop_logo' => FileService::getFileUrl(ConfigService::get('website', 'shop_logo')),

            'pc_logo' => FileService::getFileUrl(ConfigService::get('website', 'pc_logo')),
            'pc_title' => ConfigService::get('website', 'pc_title', ''),
            'pc_ico' => FileService::getFileUrl(ConfigService::get('website', 'pc_ico')),
            'pc_desc' => ConfigService::get('website', 'pc_desc', ''),
            'pc_keywords' => ConfigService::get('website', 'pc_keywords', ''),
            'shop_title' => ConfigService::get('website', 'shop_title', 'AI时代，企业化AI工具的新星'),

            'h5_favicon' => FileService::getFileUrl(ConfigService::get('website', 'h5_favicon')),

            'share_title'   => ConfigService::get('website', 'share_title', ''),
            'share_desc'    => ConfigService::get('website', 'share_desc', ''),
            'share_image'   => $image,
        ];
    }


    /**
     * @notes 设置网站信息
     * @param array $params
     * @author 段誉
     * @date 2021/12/28 15:43
     */
    public static function setWebsiteInfo(array $params)
    {
        $h5favicon = FileService::setFileUrl($params['h5_favicon']);
        $favicon = FileService::setFileUrl($params['web_favicon']);
        $logo = FileService::setFileUrl($params['web_logo']);
        $login = FileService::setFileUrl($params['login_image']);
        $shopLogo = FileService::setFileUrl($params['shop_logo']);
        $pcLogo = FileService::setFileUrl($params['pc_logo']);
        $pcIco = FileService::setFileUrl($params['pc_ico'] ?? '');
        $image = FileService::setFileUrl($params['share_image'] ?? '');

        ConfigService::set('website', 'name', $params['name']);
        ConfigService::set('website', 'web_favicon', $favicon);
        ConfigService::set('website', 'web_logo', $logo);
        ConfigService::set('website', 'login_image', $login);
        ConfigService::set('website', 'shop_name', $params['shop_name']);
        ConfigService::set('website', 'shop_logo', $shopLogo);
        ConfigService::set('website', 'pc_logo', $pcLogo);

        ConfigService::set('website', 'pc_title', $params['pc_title']);
        ConfigService::set('website', 'pc_ico', $pcIco);
        ConfigService::set('website', 'pc_desc', $params['pc_desc'] ?? '');
        ConfigService::set('website', 'pc_keywords', $params['pc_keywords'] ?? '');

        ConfigService::set('website', 'h5_favicon', $h5favicon);
        ConfigService::set('website', 'shop_title', $params['shop_title']);

        ConfigService::set('website', 'share_image', $image);
        ConfigService::set('website', 'share_title', $params['share_title'] ?? '');
        ConfigService::set('website', 'share_desc', $params['share_desc'] ?? '');
    }


    /**
     * @notes 获取版权备案
     * @return array
     * @author 段誉
     * @date 2021/12/28 16:09
     */
    public static function getCopyright(): array
    {
        return ConfigService::get('copyright', 'config', []);
    }


    /**
     * @notes 设置版权备案
     * @param array $params
     * @return bool
     * @author 段誉
     * @date 2022/8/8 16:33
     */
    public static function setCopyright(array $params)
    {
        try {
            if (!is_array($params['config'])) {
                throw new \Exception('参数异常');
            }
            ConfigService::set('copyright', 'config', $params['config'] ?? []);
            return true;
        } catch (\Exception $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }


    /**
     * @notes 设置政策协议
     * @param array $params
     * @author ljj
     * @date 2022/2/15 10:59 上午
     */
    public static function setAgreement(array $params)
    {
        $serviceContent = clear_file_domain($params['service_content'] ?? '');
        $privacyContent = clear_file_domain($params['privacy_content'] ?? '');
        ConfigService::set('agreement', 'service_title', $params['service_title'] ?? '');
        ConfigService::set('agreement', 'service_content', $serviceContent);
        ConfigService::set('agreement', 'privacy_title', $params['privacy_title'] ?? '');
        ConfigService::set('agreement', 'privacy_content', $privacyContent);
    }


    /**
     * @notes 获取政策协议
     * @return array
     * @author ljj
     * @date 2022/2/15 11:15 上午
     */
    public static function getAgreement(): array
    {
        $config = [
            'service_title' => ConfigService::get('agreement', 'service_title'),
            'service_content' => ConfigService::get('agreement', 'service_content'),
            'privacy_title' => ConfigService::get('agreement', 'privacy_title'),
            'privacy_content' => ConfigService::get('agreement', 'privacy_content'),
        ];

        $config['service_content'] = get_file_domain($config['service_content']);
        $config['privacy_content'] = get_file_domain($config['privacy_content']);

        return $config;
    }

    public static function setClient($params)
    {
        try {
            if (isset($params['windows'])) {
                ConfigService::set('client_download', 'windows', $params['windows']);
            }
            if (isset($params['mac_apple'])) {
                ConfigService::set('client_download', 'mac_apple', $params['mac_apple']);
            }
            if (isset($params['mac_intel'])) {
                ConfigService::set('client_download', 'mac_intel', $params['mac_intel']);
            }
            if (isset($params['android'])) {
                ConfigService::set('client_download', 'android', $params['android']);
            }
            if (isset($params['mini_programs'])) {
                ConfigService::set('client_download', 'mini_programs', $params['mini_programs']);
            }
            if (isset($params['h5'])) {
                ConfigService::set('client_download', 'h5', $params['h5']);
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function getClient()
    {
        return [
            'windows' =>  ConfigService::get('client_download','windows',''),
            'mac_intel' =>  ConfigService::get('client_download','mac_intel',''),
            'mac_apple' =>  ConfigService::get('client_download','mac_apple',''),
            'android' =>  ConfigService::get('client_download','android',''),
            'mini_programs' =>  ConfigService::get('client_download','mini_programs',''),
            'h5' =>  ConfigService::get('client_download','h5',''),
        ];
    }
}
