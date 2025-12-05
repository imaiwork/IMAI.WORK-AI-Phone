<?php
// 应用公共文件
use app\common\service\FileService;
use think\helper\Str;

/**
 * @notes 生成密码加密密钥
 * @param string $plaintext
 * @param string $salt
 * @return string
 * @author 段誉
 * @date 2021/12/28 18:24
 */
function create_password(string $plaintext, string $salt): string
{
    return md5($salt . md5($plaintext . $salt));
}


/**
 * @notes 随机生成token值
 * @param string $extra
 * @return string
 * @author 段誉
 * @date 2021/12/28 18:24
 */
function create_token(string $extra = ''): string
{
    $salt = env('project.unique_identification', '1234');
    $encryptSalt = md5($salt . uniqid());
    return md5($salt . $extra . time() . $encryptSalt);
}


/**
 * @notes 截取某字符字符串
 * @param $str
 * @param string $symbol
 * @return string
 * @author 段誉
 * @date 2021/12/28 18:24
 */
function substr_symbol_behind($str, $symbol = '.'): string
{
    $result = strripos($str, $symbol);
    if ($result === false) {
        return $str;
    }
    return substr($str, $result + 1);
}


/**
 * @notes 对比php版本
 * @param string $version
 * @return bool
 * @author 段誉
 * @date 2021/12/28 18:27
 */
function compare_php(string $version): bool
{
    return version_compare(PHP_VERSION, $version) >= 0 ? true : false;
}


/**
 * @notes 检查文件是否可写
 * @param string $dir
 * @return bool
 * @author 段誉
 * @date 2021/12/28 18:27
 */
function check_dir_write(string $dir = ''): bool
{
    $route = root_path() . '/' . $dir;
    return is_writable($route);
}


/**
 * 多级线性结构排序
 * 转换前：
 * [{"id":1,"pid":0,"name":"a"},{"id":2,"pid":0,"name":"b"},{"id":3,"pid":1,"name":"c"},
 * {"id":4,"pid":2,"name":"d"},{"id":5,"pid":4,"name":"e"},{"id":6,"pid":5,"name":"f"},
 * {"id":7,"pid":3,"name":"g"}]
 * 转换后：
 * [{"id":1,"pid":0,"name":"a","level":1},{"id":3,"pid":1,"name":"c","level":2},{"id":7,"pid":3,"name":"g","level":3},
 * {"id":2,"pid":0,"name":"b","level":1},{"id":4,"pid":2,"name":"d","level":2},{"id":5,"pid":4,"name":"e","level":3},
 * {"id":6,"pid":5,"name":"f","level":4}]
 * @param array $data 线性结构数组
 * @param string $symbol 名称前面加符号
 * @param string $name 名称
 * @param string $id_name 数组id名
 * @param string $parent_id_name 数组祖先id名
 * @param int $level 此值请勿给参数
 * @param int $parent_id 此值请勿给参数
 * @return array
 */
function linear_to_tree($data, $sub_key_name = 'sub', $id_name = 'id', $parent_id_name = 'pid', $parent_id = 0)
{
    $tree = [];
    foreach ($data as $row) {
        if ($row[$parent_id_name] == $parent_id) {
            $temp = $row;
            $child = linear_to_tree($data, $sub_key_name, $id_name, $parent_id_name, $row[$id_name]);
            if ($child) {
                $temp[$sub_key_name] = $child;
            }
            $tree[] = $temp;
        }
    }
    return $tree;
}


/**
 * @notes 删除目标目录
 * @param $path
 * @param $delDir
 * @return bool|void
 * @author 段誉
 * @date 2022/4/8 16:30
 */
function del_target_dir($path, $delDir)
{
    //没找到，不处理
    if (!file_exists($path)) {
        return false;
    }

    //打开目录句柄
    $handle = opendir($path);
    if ($handle) {
        while (false !== ($item = readdir($handle))) {
            if ($item != "." && $item != "..") {
                if (is_dir("$path/$item")) {
                    del_target_dir("$path/$item", $delDir);
                } else {
                    unlink("$path/$item");
                }
            }
        }
        closedir($handle);
        if ($delDir) {
            return rmdir($path);
        }
    } else {
        if (file_exists($path)) {
            return unlink($path);
        }
        return false;
    }
}


/**
 * @notes 下载文件
 * @param $url
 * @param $saveDir
 * @param $fileName
 * @return string
 * @author 段誉
 * @date 2022/9/16 9:53
 */
function download_file($url, $saveDir, $fileName)
{
    if (!file_exists($saveDir)) {
        mkdir($saveDir, 0775, true);
    }
    $fileSrc = $saveDir . $fileName;
    file_exists($fileSrc) && unlink($fileSrc);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    $file = curl_exec($ch);
    curl_close($ch);
    $resource = fopen($fileSrc, 'a');
    fwrite($resource, $file);
    fclose($resource);
    if (filesize($fileSrc) == 0) {
        unlink($fileSrc);
        return '';
    }
    return $fileSrc;
}


/**
 * @notes 去除内容图片域名
 * @param $content
 * @return array|string|string[]
 * @author 段誉
 * @date 2022/9/26 10:43
 */
function clear_file_domain($content)
{
    $fileUrl = FileService::getFileUrl();
    $pattern = '/<img[^>]*\bsrc=["\']' . preg_quote($fileUrl, '/') . '([^"\']+)["\']/i';
    return preg_replace($pattern, '<img src="$1"', $content);
}

/**
 * @notes 设置内容图片域名
 * @param $content
 * @return array|string|string[]|null
 * @author 段誉
 * @date 2024/2/5 16:36
 */
function get_file_domain($content)
{
    $imgPreg = '/(<img .*?src=")[^https|^http](.*?)(".*?>)/is';
    $videoPreg = '/(<video .*?src=")[^https|^http](.*?\.mp4)(".*?>)/is';
    $fileUrl = FileService::getFileUrl();
    $content = preg_replace($imgPreg, "\${1}$fileUrl\${2}\${3}", $content);
    return preg_replace($videoPreg, "\${1}$fileUrl\${2}\${3}", $content);
}


/**
 * @notes uri小写
 * @param $data
 * @return array|string[]
 * @author 段誉
 * @date 2022/7/19 14:50
 */
function lower_uri($data)
{
    if (!is_array($data)) {
        $data = [$data];
    }
    return array_map(function ($item) {
        return strtolower(Str::camel($item));
    }, $data);
}


/**
 * @notes 获取无前缀数据表名
 * @param $tableName
 * @return mixed|string
 * @author 段誉
 * @date 2022/12/12 15:23
 */
function get_no_prefix_table_name($tableName)
{
    $tablePrefix = config('database.connections.mysql.prefix');
    $prefixIndex = strpos($tableName, $tablePrefix);
    if ($prefixIndex !== 0 || $prefixIndex === false) {
        return $tableName;
    }
    $tableName = substr_replace($tableName, '', 0, strlen($tablePrefix));
    return trim($tableName);
}


/**
 * @notes 生成编码
 * @param $table
 * @param $field
 * @param string $prefix
 * @param int $randSuffixLength
 * @param array $pool
 * @return string
 * @author 段誉
 * @date 2023/2/23 11:35
 */
function generate_sn($table, $field, $prefix = '', $randSuffixLength = 4, $pool = []): string
{
    $suffix = '';
    for ($i = 0; $i < $randSuffixLength; $i++) {
        if (empty($pool)) {
            $suffix .= rand(0, 9);
        } else {
            $suffix .= $pool[array_rand($pool)];
        }
    }
    $sn = $prefix . date('YmdHis') . $suffix;
    if (app()->make($table)->where($field, $sn)->find()) {
        return generate_sn($table, $field, $prefix, $randSuffixLength, $pool);
    }
    return $sn;
}


/**
 * @notes 格式化金额
 * @param $float
 * @return int|mixed|string
 * @author 段誉
 * @date 2023/2/24 11:20
 */
function format_amount($float)
{
    if ($float == intval($float)) {
        return intval($float);
    } elseif ($float == sprintf('%.1f', $float)) {
        return sprintf('%.1f', $float);
    }
    return $float;
}

/**
 * @desc 全局报错方法
 * @param string $msg
 * @param int $is_log 是否记录日志 默认记录
 * @param int $code
 * @param int $show
 * @param array $data
 * @return mixed
 * @date 2024/6/6 15:57
 * @author dagouzi
 */
function message(string $msg = 'error', int $is_log = 0, int $code = 0, int $show = 0,  array $data = [])
{
    $data = compact('code', 'is_log', 'show', 'msg', 'data');
    $response = \think\Response::create($data, 'json', 200);
    throw new \think\exception\HttpResponseException($response);
}

/**
 * @notes bytes 动态转换单位
 * @param int $bytes
 * @return string
 * @author 段誉
 * @date 2023/2/23 18:43
 */
function format_bytes($bytes)
{
    if ($bytes > 1024 * 1024 * 1024 * 1024) {
        return round($bytes / 1024 / 1024 / 1024 / 1024, 2) . 'TB';
    } elseif ($bytes > 1024 * 1024 * 1024) {
        return round($bytes / 1024 / 1024 / 1024, 2) . 'GB';
    } elseif ($bytes > 1024 * 1024) {
        return round($bytes / 1024 / 1024, 2) . 'MB';
    } elseif ($bytes > 1024) {
        return round($bytes / 1024, 2) . 'KB';
    } else {
        return $bytes . 'B';
    }
}

/**
 * 生成唯一ID
 * @return string
 */
function generate_unique_task_id(): string
{
    return md5(date('YmdHis') . uniqid() . mt_rand(1000000, 9999999));
}


/**
 * 面试ai 获取 评分
 * @notes 格式化JSON
 * @param string $text
 * @return array
 * @author dagouzi
 * @date 2025/3/24 15:57
 */
function format_json($text)
{

    // 使用正则表达式匹配JSON部分
    $jsonPattern = '/\{(?:[^{}]|(?R))*\}/';
    preg_match($jsonPattern,$text, $matches);

    // 输出匹配到的JSON字符串
    $jsonString = $matches[0] ?? 'No JSON found';

    $result = json_decode($jsonString, true);
    return $result;
}

function clogger($log_content, $log_filename = 'log'){
    return '';
    $max_size = 500000;
    $path = public_path().'/logs/knowledge';
    !is_dir($path) && mkdir($path, 0777, true);
    file_put_contents($path.'/'.date('Ymd').'_'.$log_filename.'.log', $log_content.PHP_EOL, FILE_APPEND);
    
}


function formatMarkdown(string $content): string
{
    $content = strip_tags($content);
    $content = preg_replace(['/#+/', '/`+/', '/\*+/', '/\|+/', '/\n(&gt;|\\>)/', '/^>{1}/'], '', $content);
    $content = str_replace("\n\n", "\n", $content);
    $content = str_replace(['根据知识库内容，', '根据知识库内容', '根据提供的知识库内容，', '根据提供的知识库内容'], '', $content);
    return $content;
}

/**
 * @notes 卡密编码
 * @param $table
 * @param $field
 * @param string $prefix
 * @param int $randSuffixLength
 * @param int $ruleType 生成规则：1-批次编号+随机字母；2-批次编号+随机数字；
 * @return string
 * @author ljj
 * @date 2024/5/8 10:14 上午
 */
function card_sn($table, $field, $prefix = 'K', $randSuffixLength = 6, $ruleType = 2) : string
{
    $string = $prefix;
    $letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    for ($i = 0; $i < $randSuffixLength; $i++) {
        if ($ruleType == 1) {
            $string .= $letters[rand(0, strlen($letters) - 1)];
        }
        if ($ruleType == 2) {
            $string .= rand(0, 9);
        }
    }
    if (app()->make($table)->where($field, $string)->find()) {
        return generate_sn($table, $field, $prefix, $randSuffixLength, $ruleType);
    }
    return $string;
}

/***
 * @notes 格式化金额去除右边多余的0
 * @param $value
 * @return string
 */
function format_amount_zero($value): string
{
    $value = strval($value);
    if (str_contains($value, '.')) {
        $value = rtrim($value, '0');
        return rtrim($value, '.');
    }
    return $value;
}

/**
 * @notes 生成随机字符串
 * @param $length
 * @return string
 * @author fzr
 */
function generate_random_str($length): string
{
    $chars     = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $str       = '';
    $charCount = strlen($chars);

    for ($i = 0; $i < $length; $i++) {
        $randomIndex = rand(0, $charCount - 1);
        $str         .= $chars[$randomIndex];
    }

    return $str;
}