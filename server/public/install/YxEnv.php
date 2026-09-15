<?php

class YxEnv
{
    /**
     * 环境变量数据
     * @var array
     */
    protected $data = [];
    protected $filePath = '';

    public function __construct()
    {
        //$this->data = $_ENV;
    }

    /**
     * 读取环境变量定义文件
     * @access public
     * @param string $file 环境变量定义文件
     * @return void
     */
    public function load($file)
    {
        $this->filePath = $file;
        $env = parse_ini_file($file, true);
        $this->set($env);
    }

    public function makeEnv($file)
    {
        if (!file_exists($file)) {
            try {
                touch($file);
            } catch (Exception $e) {
                return;
            }
        }
    }

    /**
     * 获取环境变量值
     * @access public
     * @param string $name 环境变量名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get($name = null, $default = null, $php_prefix = true)
    {
        if (is_null($name)) {
            return $this->data;
        }

        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return $this->getEnv($name, $default, $php_prefix);
    }

    protected function getEnv($name, $default = null, $php_prefix = true)
    {
        if ($php_prefix) {
            $name = 'PHP_' . $name;
        }

        $result = getenv($name);

        if (false === $result) {
            return $default;
        }

        if ('false' === $result) {
            $result = false;
        } elseif ('true' === $result) {
            $result = true;
        }

        if (!isset($this->data[$name])) {
            $this->data[$name] = $result;
        }

        return $result;
    }

    /**
     * Notes: 写入Env文件
     * @param $envFilePath
     * @param array $databaseEnv
     * @param array $keyInfo
     * @author luzg(2020/8/27 18:12)
     */
    public function putEnv($envFilePath, array $databaseEnv, $keyInfo = [])
    {

        $applyDbEnv = [
            'DATABASE.HOSTNAME' => $databaseEnv['host'],
            'DATABASE.DATABASE' => $databaseEnv['name'],
            'DATABASE.USERNAME' => $databaseEnv['user'],
            'DATABASE.PASSWORD' => $databaseEnv['password'],
            'DATABASE.HOSTPORT' => $databaseEnv['port'],
            'DATABASE.PREFIX'   => $databaseEnv['prefix'],
            
            'PGSQL.HOSTNAME' => $databaseEnv['pg_host'],
            'PGSQL.DATABASE' => $databaseEnv['pg_name'],
            'PGSQL.USERNAME' => $databaseEnv['pg_user'],
            'PGSQL.PASSWORD' => $databaseEnv['pg_password'],
            'PGSQL.HOSTPORT' => $databaseEnv['pg_port'],
            'PGSQL.PREFIX'   => $databaseEnv['pg_prefix'],

        ];

        $envLine = array_merge($this->data, $applyDbEnv);
        $content = '';
        $lastPrefix = '';

        global $uniqueSalt;

        foreach ($envLine as $index => $value) {

            //TODO 唯一标识
            if ($index == 'PROJECT.UNIQUE_IDENTIFICATION' && !empty($uniqueSalt)) {

                $value = $uniqueSalt;
            }

            //TODO 中台KEY
            if ($index == 'PROJECT_KEY.API_KEY') {


                $value = $keyInfo['api_key'];
            }

            //TODO 中台签名
            if ($index == 'PROJECT_KEY.SIGN_KEY') {


                $value = $keyInfo['sign_key'];
            }

            //回调鉴权令牌：新装站点直接生成随机值，不留给站长手填；只在本站闭环，无需与中台协调
            if ($index == 'NOTIFY.TOKEN' && empty($value)) {

                $value = bin2hex(random_bytes(24));
            }

            //回调迁移宽限期：随令牌一起填成安装时刻+1天的时间戳，到点自动失效
            if ($index == 'NOTIFY.LEGACY_UNTIL' && empty($value)) {

                $value = time() + 86400;
            }

            //TODO 域名
            if ($index == 'APP.HOST') {

                // 判断是否 HTTPS
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? "https://" : "http://";

                // 获取主机名
                $host = $_SERVER['HTTP_HOST'];

                // 组合完整的 URL
                $value = $protocol . $host;
            }

            @list($prefix, $key) = explode('.', $index);

            if ($prefix != $lastPrefix && $key != null) {
                if ($lastPrefix != '')
                    $content .= "\n";
                $content .= "[$prefix]\n";
                $lastPrefix = $prefix;
            }

            if ($prefix != $lastPrefix && $key == null) {
                $content .= "$index = \"$value\"\n";
            } else {
                $content .= "$key = \"$value\"\n";
            }
        }
        if (!empty($content)) {
            file_put_contents($envFilePath, $content);
        }
    }


    /**
     * 设置环境变量值
     * @access public
     * @param string|array $env 环境变量
     * @param mixed $value 值
     * @return void
     */
    public function set($env, $value = null)
    {
        if (is_array($env)) {

            foreach ($env as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        $this->data[$key . '.' . $k] = $v;
                    }
                } else {
                    $this->data[$key] = $val;
                }
            }
        } else {
            $name = strtoupper(str_replace('.', '_', $env));

            $this->data[$name] = $value;
        }
    }
}
