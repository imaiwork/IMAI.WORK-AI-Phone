<?php

/** 安装界面需要的各种模块 */

class installModel
{
   private $host;
   private $name;
   private $user;
   private $encoding;
   private $password;
   private $port;
   private $prefix;
   private $successTable = [];
   /**
    * @var bool
    */
   private $allowNext = true;
   /**
    * @var PDO|string
    */
   private $dbh = null;
   /**
    * @var bool
    */
   private $clearDB = false;
   /**
    * @var string
    */
   private $lastError = '';

   /**
    * Notes: php版本
    * @author luzg(2020/8/25 9:56)
    * @return string
    */
   public function getPhpVersion()
   {
      return PHP_VERSION;
   }

   /**
    * Notes: 当前版本是否符合
    * @author luzg(2020/8/25 9:57)
    * @return string
    */
   public function checkPHP()
   {
      return $result = version_compare(PHP_VERSION, '8.0.0') >= 0 ? 'ok' : 'fail';
   }

   /**
    * Notes: 是否有PDO
    * @author luzg(2020/8/25 9:57)
    * @return string
    */
   public function checkPDO()
   {
      return $result = extension_loaded('pdo') ? 'ok' : 'fail';
   }

   /**
    * Notes: 是否有PDO::MySQL
    * @author luzg(2020/8/25 9:58)
    * @return string
    */
   public function checkPDOMySQL()
   {
      return $result = extension_loaded('pdo_mysql') ? 'ok' : 'fail';
   }

   /**
    * Notes: 是否支持JSON
    * @author luzg(2020/8/25 9:58)
    * @return string
    */
   public function checkJSON()
   {
      return $result = extension_loaded('json') ? 'ok' : 'fail';
   }

   /**
    * Notes: 是否支持openssl
    * @author luzg(2020/8/25 9:58)
    * @return string
    */
   public function checkOpenssl()
   {
      return $result = extension_loaded('openssl') ? 'ok' : 'fail';
   }

   /**
    * Notes: 是否支持mbstring
    * @author luzg(2020/8/25 9:58)
    * @return string
    */
   public function checkMbstring()
   {
      return $result = extension_loaded('mbstring') ? 'ok' : 'fail';
   }

   /**
    * Notes: 是否支持zlib
    * @author luzg(2020/8/25 9:59)
    * @return string
    */
   public function checkZlib()
   {
      return $result = extension_loaded('zlib') ? 'ok' : 'fail';
   }

   /**
    * Notes: 是否支持curl
    * @author luzg(2020/8/25 9:59)
    * @return string
    */
   public function checkCurl()
   {
      return $result = extension_loaded('curl') ? 'ok' : 'fail';
   }

   /**
    * Notes: 检查GD2扩展
    * @author luzg(2020/8/26 9:59)
    * @return string
    */
   public function checkGd2()
   {
      return $result = extension_loaded('gd') ? 'ok' : 'fail';
   }

   /**
    * Notes: 检查Dom扩展
    * @author luzg(2020/8/26 9:59)
    * @return string
    */
   public function checkDom()
   {
      return $result = extension_loaded('dom') ? 'ok' : 'fail';
   }

   /**
    * Notes: 是否支持filter
    * @author luzg(2020/8/25 9:59)
    * @return string
    */
   public function checkFilter()
   {
      return $result = extension_loaded('filter') ? 'ok' : 'fail';
   }

   /**
    * Notes: 是否支持iconv
    * @author luzg(2020/8/25 9:59)
    * @return string
    */
   public function checkIconv()
   {
      return $result = extension_loaded('iconv') ? 'ok' : 'fail';
   }


   /**
    * Notes: 检查fileinfo扩展
    * @author 段誉(2021/6/28 11:03)
    * @return string
    */
   public function checkFileInfo()
   {
      return $result = extension_loaded('fileinfo') ? 'ok' : 'fail';
   }



   /**
    * Notes: 取得临时目录路径
    * @author luzg(2020/8/25 10:05)
    * @return array
    */
   public function getTmpRoot()
   {
      $path = $this->getAppRoot() . '/runtime';
      return [
         'path'     => $path,
         'exists'   => is_dir($path),
         'writable' => is_writable($path),
      ];
   }

   /**
    * Notes: 检查临时路径
    * @author luzg(2020/8/25 10:06)
    * @return string
    */
   public function checkTmpRoot()
   {
      $tmpRoot = $this->getTmpRoot()['path'];
      return $result = (is_dir($tmpRoot) and is_writable($tmpRoot)) ? 'ok' : 'fail';
   }

   /**
    * Notes: SESSION路径是否可写
    * @author luzg(2020/8/25 10:06)
    * @return mixed
    */
   public function getSessionSavePath()
   {
      $sessionSavePath = preg_replace("/\d;/", '', session_save_path());

      return [
         'path'     => $sessionSavePath,
         'exists'   => is_dir($sessionSavePath),
         'writable' => is_writable($sessionSavePath),
      ];
   }

   /**
    * Notes: 检查session路径可写状态
    * @author luzg(2020/8/25 10:13)
    * @return string
    */
   public function checkSessionSavePath()
   {
      $sessionSavePath = preg_replace("/\d;/", '', session_save_path());
      $result = (is_dir($sessionSavePath) and is_writable($sessionSavePath)) ? 'ok' : 'fail';
      if ($result == 'fail') return $result;

      file_put_contents($sessionSavePath . '/zentaotest', 'zentao');
      $sessionContent = file_get_contents($sessionSavePath . '/zentaotest');
      if ($sessionContent == 'zentao') {
         unlink($sessionSavePath . '/zentaotest');
         return 'ok';
      }
      return 'fail';
   }

   /**
    * Notes: 取得data目录是否可选
    * @author luzg(2020/8/25 10:58)
    * @return array
    */
   public function getDataRoot()
   {
      $path = $this->getAppRoot();
      return [
         'path'     => $path . 'www' . DS . 'data',
         'exists'   => is_dir($path),
         'writable' => is_writable($path),
      ];
   }

   /**
    * Notes: 取得root路径
    * @author luzg(2020/8/25 11:02)
    * @return string
    */
   public function checkDataRoot()
   {
      $dataRoot = $this->getAppRoot() . 'www' . DS . 'data';
      return $result = (is_dir($dataRoot) and is_writable($dataRoot)) ? 'ok' : 'fail';
   }

   /**
    * Notes: 取得php.ini信息
    * @author luzg(2020/8/25 11:03)
    * @return string
    */
   public function getIniInfo()
   {
      $iniInfo = '';
      ob_start();
      phpinfo(1);
      $lines = explode("\n", strip_tags(ob_get_contents()));
      ob_end_clean();
      foreach ($lines as $line) if (strpos($line, 'ini') !== false) $iniInfo .= $line . "\n";
      return $iniInfo;
   }


   /**
    * Notes: 创建安装锁定文件
    * @author luzg(2020/8/28 11:32)
    * @return bool
    */
   public function mkLockFile()
   {
      return touch($this->getAppRoot() . '/install.lock');
   }

   /**
    * Notes: 检查之前是否有安装
    * @author luzg(2020/8/28 11:36)
    */
   public function appIsInstalled()
   {
      return file_exists($this->getAppRoot() . '/install.lock');
   }

   /**
    * Notes: 取得配置信息
    * @author luzg(2020/8/25 11:05)
    * @param string $dbName 数据库名称
    * @param array $connectionInfo 连接信息
    * @return stdclass
    * @throws Exception
    */
   public function checkConfig($dbName, $connectionInfo)
   {
      $return = new stdclass();
      $return->result = 'ok';

      /* Connect to database. */
      $this->setDBParam($connectionInfo);

      $this->dbh = $this->connectDB();
      //        if (strpos($dbName, '.') !== false) {
      //            $return->result = 'fail';
      //            $return->error = '没有发现数据库信息';
      //            return $return;
      //        }
      if (!is_object($this->dbh)) {
         $return->result = 'fail';
         $return->error = '安装错误，请检查连接信息:' . mb_strcut($this->dbh, 0, 30) . '...';
         echo $this->dbh;
         return $return;
      }

      /* Get mysql version. */
      $version = $this->getMysqlVersion();

      /* check mysql sql_model */
      //        if(!$this->checkSqlMode($version)) {
      //            $return->result = 'fail';
      //            $return->error = '请在mysql配置文件修改sql-mode添加NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
      //            return $return;
      //        }

      /* If database no exits, try create it. */
      //        if ( !$this->dbExists()) {
      //            if ( !$this->createDB($version)) {
      //                $return->result = 'fail';
      //                $return->error = '创建数据库错误';
      //                return $return;
      //            }
      //        } elseif ($this->tableExits() and !$this->clearDB) {
      //            $return->result = 'fail';
      //            $return->error = '数据表已存在，您之前可能已安装本系统，如需继续安装请选择新的数据库。';
      //            return $return;
      //        } elseif ($this->dbExists() and $this->clearDB) {
      //            if (!$this->dropDb($connectionInfo['name'])) {
      //                $return->result = 'fail';
      //                $return->error = '数据表已经存在，删除已存在库错误,请手动清除';
      //                return $return;
      //            } else {
      //                if ( !$this->createDB($version)) {
      //                    $return->result = 'fail';
      //                    $return->error = '创建数据库错误!';
      //                    return $return;
      //                }
      //            }
      //        }

      /* Create tables. */
      if (!$this->createTable($version, $connectionInfo)) {
         $return->result = 'fail';
         $return->error = '创建表格失败';
         return $return;
      }

      return $return;
   }

   /**
    * Notes: Pgsql配置
    * @param $connectionInfo
    * @return stdclass
    */
   public function checkPgConfig($connectionInfo): stdclass
   {
      $return = new stdclass();
      $return->result = 'ok';

      try {
         $host     = $connectionInfo['pg_host'] ?? '';
         $port     = $connectionInfo['pg_port'] ?? '';
         $dbname   = $connectionInfo['pg_name'] ?? '';
         $username = $connectionInfo['pg_user'] ?? '';
         $password = $connectionInfo['pg_password'] ?? '';
         $prefix   = trim($connectionInfo['pg_prefix'] ?? '');

         $pdo = new \PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
         $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

         if (!is_object($pdo)) {
            $return->result = 'fail';
            $return->error = 'Postgresql数据库连接失败,请检查连接信息...请删除mysql数据库重新安装';
            return $return;
         }


         if ($this->clearDB == true) {
            $tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
            while ($row = $tables->fetch(PDO::FETCH_ASSOC)) {
               $tableName = $row['table_name'];
               try {
                  $pdo->exec("DROP TABLE IF EXISTS $tableName CASCADE");
               } catch (PDOException $e) {
                  $return->result = 'fail';
                  $return->error = 'Postgresql清空所有表出错:' . $e->getMessage() . '...请删除mysql数据库重新安装';
                  return $return;
               }
            }
         }

         $resVector = $pdo->query("SELECT installed_version FROM pg_available_extensions WHERE name = 'vector';");
         $fetVector = $resVector->fetch();
         if (!$fetVector || empty($fetVector['installed_version'])) {
            try {
               $pdo->exec('CREATE EXTENSION vector;');
            } catch (Exception $e) {
               $return->result = 'fail';
               $return->error = 'Postgresql启用vector扩展异常,请检查是否已安装...请删除mysql数据库重新安装';
               return $return;
            }
         }

         $resVector = $pdo->query("SELECT installed_version FROM pg_available_extensions WHERE name = 'zhparser';");
         $fetVector = $resVector->fetch();
         if (!$fetVector || empty($fetVector['installed_version'])) {
            try {
               $pdo->exec('CREATE EXTENSION zhparser;');
            } catch (Exception $e) {
               $return->result = 'fail';
               $return->error = 'Postgresql启用zhparser扩展异常,请检查是否已安装...请删除mysql数据库重新安装';
               return $return;
            }
         }

         // 移除类型和函数,不移除会报错
         $tableStructCount = $pdo->query("SELECT COUNT(*) FROM pg_type WHERE typname = 'tablestruct';");
         if ($tableStructCount->fetch()['count'] ?? 0) {
            try {
               $pdo->exec('DROP FUNCTION table_msg("a_schema_name" "pg_catalog"."varchar", "a_table_name" "pg_catalog"."varchar") CASCADE;');
            } catch (Exception $e) {
            }

            try {
               $pdo->exec('DROP FUNCTION table_msg(a_table_name varchar) CASCADE;');
            } catch (Exception $e) {
            }

            try {
               $pdo->exec('DROP FUNCTION pgsql_type(a_type varchar) CASCADE;');
            } catch (Exception $e) {
            }

            try {
               $pdo->exec('DROP TYPE tablestruct;');
            } catch (Exception $e) {
            }
         }

         $dbFile  = $this->getInstallRoot() . '/db/pg.sql';
         $content = str_replace(";\r\n", ";\n", file_get_contents($dbFile));
         $content = str_replace('la_', $prefix, $content);
         $pdo->exec($content);
      } catch (PDOException $e) {
         $return->result = 'fail';
         $return->error = 'pg数据库配置有误，请删除mysql数据库重新安装' . str_replace('"', "'", $e->getMessage());
         return $return;
      }

      return $return;
   }
   /**
    * Notes: 设置数据库相关信息
    * @author luzg(2020/8/25 11:17)
    * @param $post
    */
   public function setDBParam($post)
   {
      $this->host = $post['host'];
      $this->name = $post['name'];
      $this->user = $post['user'];
      $this->encoding = 'utf8mb4';
      $this->password = $post['password'];
      $this->port = $post['port'];
      $this->prefix = $post['prefix'];
      $this->clearDB = $post['clear_db'] == 'on';
   }

   /**
    * Notes: 连接数据库
    * @author luzg(2020/8/25 11:56)
    * @return PDO|string
    */
   public function connectDB()
   {
      $dsn = "mysql:host={$this->host};dbname={$this->name};port={$this->port}";
      try {
         $dbh = new PDO($dsn, $this->user, $this->password);
         $dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
         $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
         $dbh->exec("SET NAMES {$this->encoding}");
         $dbh->exec("SET NAMES {$this->encoding}");
         try {
            $dbh->exec("SET GLOBAL sql_mode='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';");
         } catch (Exception $e) {
         }
         return $dbh;
      } catch (PDOException $exception) {
         return $exception->getMessage();
      }
   }

   /**
    * Notes: 检查数据库是否存在
    * @author luzg(2020/8/25 11:56)
    * @return mixed
    */
   public function dbExists()
   {
      $sql = "SHOW DATABASES like '{$this->name}'";
      return $this->dbh->query($sql)->fetch();
   }

   /**
    * Notes: 检查表是否存在
    * @author luzg(2020/8/25 11:56)
    * @return mixed
    */
   public function tableExits()
   {
      $configTable = sprintf("'%s'", $this->prefix . TESTING_TABLE);
      $sql = "SHOW TABLES FROM {$this->name} like $configTable";
      return $this->dbh->query($sql)->fetch();
   }

   /**
    * Notes: 获取mysql版本号
    * @author luzg(2020/8/25 11:56)
    * @return false|string
    */
   public function getMysqlVersion()
   {
      $sql = "SELECT VERSION() AS version";
      $result = $this->dbh->query($sql)->fetch();
      return substr($result->version, 0, 3);
   }

   /**
    * @notes 检测数据库sql_mode
    * @param $version
    * @return bool
    * @author 段誉
    * @date 2021/8/27 17:17
    */
   public function checkSqlMode($version)
   {
      $sql = "SELECT @@global.sql_mode";
      $result = $this->dbh->query($sql)->fetch();
      $result = (array)$result;

      if ($version >= 5.7 && $version < 8.0) {
         if ((strpos($result['@@global.sql_mode'], 'NO_AUTO_CREATE_USER') !== false)
            && (strpos($result['@@global.sql_mode'], 'NO_ENGINE_SUBSTITUTION') !== false)
         ) {
            return true;
         }
         return false;
      }
      return true;
   }


   /**
    * Notes: 创建数据库
    * @author luzg(2020/8/25 11:57)
    * @param $version
    * @return mixed
    */
   public function createDB($version)
   {
      $sql = "CREATE DATABASE `{$this->name}`";
      if ($version > 4.1) $sql .= " DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
      return $this->dbh->query($sql);
   }

   /**
    * Notes: 创建表
    * @author luzg(2020/8/25 11:57)
    * @param $version
    * @param $post
    * @return bool
    * @throws Exception
    */
   public function createTable($version, $post)
   {
      $dbFile = $this->getInstallRoot() . '/db/install.sql';
      //file_put_contents($dbFile, $this->initAccount($post), FILE_APPEND);
      $content = str_replace(";\r\n", ";\n", file_get_contents($dbFile));
      $tables = explode(";\n", $content);
      $tables[] = $this->initAccount($post);
      $installTime = microtime(true) * 10000;
      //设置链接数据库名

      foreach ($tables as $table) {
         $table = trim($table);
         if (empty($table)) continue;

         if (strpos($table, 'CREATE') !== false and $version <= 4.1) {
            $table = str_replace('DEFAULT CHARSET=utf8', '', $table);
         }
         //            elseif (strpos($table, 'DROP') !== false and $this->clearDB != false) {
         //                $table = str_replace('--', '', $table);
         //            }

         /* Skip sql that is note. */
         if (strpos($table, '--') === 0) continue;

         $table = str_replace('`la_', $this->name . '.`la_', $table);
         $table = str_replace('`la_', '`' . $this->prefix, $table);
         if ($table == 'SET NAMES utf8mb4' || $table == 'SET FOREIGN_KEY_CHECKS = 0') continue;

         if (strpos($table, 'CREATE') !== false) {
            $tableName = explode('`', $table)[1];
            $installTime += random_int(3000, 7000);
            $this->successTable[] = [$tableName, date('Y-m-d H:i:s', (int)($installTime / 10000))];
         }

         //            if (strpos($table, "INSERT INTO ") !== false) {
         //                $table = str_replace('INSERT INTO ', 'INSERT INTO ' .$this->name .'.', $table);
         //            }

         try {
            if (!$this->dbh->query($table)) return false;
         } catch (Exception $e) {
            echo 'error sql: ' . $table . "<br>";
            echo $e->getMessage() . "<br>";
            return false;
         }
      }
      return true;
   }

   /**
    * Notes: 删除数据库
    * @param $db
    * @return false|PDOStatement
    */
   public function dropDb($db)
   {
      $sql = "drop database {$db};";
      return $this->dbh->query($sql);
   }

   /**
    * Notes: 取得安装成功的表列表
    * @author luzg(2020/8/26 18:28)
    * @return array
    */
   public function getSuccessTable()
   {
      return $this->successTable;
   }

   /**
    * Notes: 创建演示数据
    * @return bool
    * @author luzg(2020/8/25 11:58)
    */

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function importAIModelData()
    {
        $this->lastError = '';
        @set_time_limit(0);

        $sqlDir = $this->getInstallRoot() . '/db/';
        $files  = glob($sqlDir . 'update*.sql') ?: [];

        usort($files, function ($a, $b) {
            preg_match('/^update(\d+)\.sql$/i', basename($a), $matchA);
            preg_match('/^update(\d+)\.sql$/i', basename($b), $matchB);

            $numA = isset($matchA[1]) ? (int)$matchA[1] : 0;
            $numB = isset($matchB[1]) ? (int)$matchB[1] : 0;

            return $numA <=> $numB;
        });

        foreach ($files as $file) {
            $content      = file_get_contents($file);
            $content      = str_replace(";\r\n", ";\n", $content);
            $insertTables = explode(";\n", $content);
            foreach ($insertTables as $table) {
                $table = trim($table);
                if ($table === '') {
                    continue;
                }
                $stripped = trim(preg_replace('/^\s*--[^\n]*$/m', '', $table));
                if ($stripped === '') {
                    continue;
                }
                // 只换前缀，不要拼库名。拼上库名会把 TRIM('`la_xxx`') 弄成
                // TRIM('库名.`iw_xxx`')，information_schema 永远查不到，幂等 ADD 会再次 1060。
                $table = str_replace('`la_', '`' . $this->prefix, $table);

                if (!$this->executeUpdateSql($table, $file)) {
                    return false;
                }
            }
        }

        // 移动图片资源
        $this->cpFiles($this->getInstallRoot() . '/uploads', $this->getAppRoot() . '/public/uploads');

        return true;
    }

    /**
     * 执行安装增量 SQL：列/索引/表已存在时跳过，避免重装或半截安装直接 Fatal。
     */
    private function executeUpdateSql(string $sql, string $file): bool
    {
        $queue = $this->splitAlterTableActions($sql) ?? [$sql];

        foreach ($queue as $one) {
            try {
                $this->dbh->query($one);
            } catch (PDOException $e) {
                if ($this->isIgnorableSchemaError($e, $one)) {
                    continue;
                }
                $this->lastError = basename($file) . ': ' . $e->getMessage();
                return false;
            }
        }

        return true;
    }

    private function isIgnorableSchemaError(PDOException $e, string $sql): bool
    {
        $code = (int)($e->errorInfo[1] ?? 0);
        // 1050 表已存在 / 1060 列已存在 / 1061 索引名重复 / 1068 主键已存在
        // 1091 删除的列或索引不存在 / 1826 外键名重复 / 1022 重复键
        $ddlCodes = [1050, 1060, 1061, 1068, 1091, 1826, 1022];
        if (in_array($code, $ddlCodes, true)) {
            return true;
        }
        if ($code === 1062 && preg_match('/^\s*(INSERT|REPLACE)\b/i', $sql)) {
            return true;
        }
        return false;
    }

    /**
     * 把 ALTER TABLE 的多个动作拆开，避免「其中一列已存在」导致整句后半段被跳过。
     */
    private function splitAlterTableActions(string $sql): ?array
    {
        if (!preg_match('/^ALTER\s+TABLE\s+((?:`[^`]+`\.|[a-zA-Z0-9_$\x80-\xff]+\.)?`[^`]+`)\s+/is', $sql, $m)) {
            return null;
        }

        $table = $m[1];
        $rest = trim(substr($sql, strlen($m[0])));
        $rest = rtrim($rest, " \t\n\r;");
        $actions = $this->splitTopLevelCommaList($rest);
        if (count($actions) <= 1) {
            return null;
        }

        $out = [];
        foreach ($actions as $action) {
            $action = trim($action);
            if ($action === '') {
                continue;
            }
            $out[] = 'ALTER TABLE ' . $table . ' ' . $action;
        }

        return $out ?: null;
    }

    private function splitTopLevelCommaList(string $text): array
    {
        $parts = [];
        $buf = '';
        $depth = 0;
        $quote = '';
        $len = strlen($text);

        for ($i = 0; $i < $len; $i++) {
            $ch = $text[$i];
            if ($quote !== '') {
                $buf .= $ch;
                if ($ch === '\\' && $i + 1 < $len) {
                    $buf .= $text[++$i];
                    continue;
                }
                if ($ch === $quote) {
                    $quote = '';
                }
                continue;
            }
            if ($ch === "'" || $ch === '"' || $ch === '`') {
                $quote = $ch;
                $buf .= $ch;
                continue;
            }
            if ($ch === '(') {
                $depth++;
                $buf .= $ch;
                continue;
            }
            if ($ch === ')') {
                $depth = max(0, $depth - 1);
                $buf .= $ch;
                continue;
            }
            if ($ch === ',' && $depth === 0) {
                $parts[] = trim($buf);
                $buf = '';
                continue;
            }
            $buf .= $ch;
        }

        if (trim($buf) !== '') {
            $parts[] = trim($buf);
        }

        return $parts;
    }

   /**
    * 将一个文件夹下的所有文件及文件夹
    * 复制到另一个文件夹里（保持原有结构）
    *
    * @param <string> $rootFrom 源文件夹地址（最好为绝对路径）
    * @param <string> $rootTo 目的文件夹地址（最好为绝对路径）
    */
   function cpFiles($rootFrom, $rootTo)
   {

      $handle = opendir($rootFrom);
      while (false !== ($file = readdir($handle))) {
         //DIRECTORY_SEPARATOR 为系统的文件夹名称的分隔符 例如：windos为'/'; linux为'/'
         $fileFrom = $rootFrom . DIRECTORY_SEPARATOR . $file;
         $fileTo = $rootTo . DIRECTORY_SEPARATOR . $file;
         if ($file == '.' || $file == '..') {
            continue;
         }

         if (is_dir($fileFrom)) {
            if (!is_dir($fileTo)) { //目标目录不存在则创建
               mkdir($fileTo, 0777);
            }
            $this->cpFiles($fileFrom, $fileTo);
         } else {
            if (!file_exists($fileTo)) {
               @copy($fileFrom, $fileTo);
               if (strstr($fileTo, "access_token.txt")) {
                  chmod($fileTo, 0777);
               }
            }
         }
      }
   }

   /**
    * Notes: 当前应用程序的相对路径
    * @author luzg(2020/8/25 10:55)
    * @return string
    */
   public function getAppRoot()
   {
      return realpath($this->getInstallRoot() . '/../../');
   }

   /**
    * Notes: 获取安装目录
    * @author luzg(2020/8/26 16:15)
    * @return string
    */
   public function getInstallRoot()
   {
      return INSTALL_ROOT;
   }

   /**
    * Notes: 目录的容量
    * @author luzg(2020/8/25 15:21)
    * @param $dir
    * @return string
    */
   public function freeDiskSpace($dir)
   {
      // M
      $freeDiskSpace = disk_free_space(realpath(__DIR__)) / 1024 / 1024;

      // G
      if ($freeDiskSpace > 1024) {
         return number_format($freeDiskSpace / 1024, 2) . 'G';
      }

      return number_format($freeDiskSpace, 2) . 'M';
   }

   /**
    * Notes: 获取状态标志
    * @author luzg(2020/8/25 16:10)
    * @param $statusSingle
    * @return string
    */
   public function correctOrFail($statusSingle)
   {
      if ($statusSingle == 'ok')
         return '<td class="layui-icon green">&#xe605;</td>';
      $this->allowNext = false;
      return '<td class="layui-icon wrong">&#x1006;</td>';
   }

   /**
    * Notes: 是否允许下一步
    * @author luzg(2020/8/25 17:29)
    * @return bool
    */
   public function getAllowNext()
   {
      return $this->allowNext;
   }

   /**
    * Notes: 检查session auto start
    * @author luzg(2020/8/25 16:55)
    * @return string
    */
   public function checkSessionAutoStart()
   {
      return $result = ini_get('session.auto_start') == '0' ? 'ok' : 'fail';
   }

   /**
    * Notes: 检查auto tags
    * @author luzg(2020/8/25 16:55)
    * @return string
    */
   public function checkAutoTags()
   {
      return $result = ini_get('session.auto_start') == '0' ? 'ok' : 'fail';
   }

   /**
    * Notes: 检查目录是否可写
    * @param $dir
    * @return string
    */
   public function checkDirWrite($dir = '')
   {
      $route = $this->getAppRoot() . '/' . $dir;
      return $result = is_writable($route) ? 'ok' : 'fail';
   }

   /**
    * Notes: 检查目录是否可写
    * @param $dir
    * @return string
    */
   public function checkSuperiorDirWrite($dir = '')
   {
      $route = $this->getAppRoot() . '/' . $dir;
      return $result = is_writable($route) ? 'ok' : 'fail';
   }


   /**
    * Notes: 初始化管理账号
    * @param $post
    * @return string
    */
   public function initAccount($post)
   {
      $time = time();
      $salt = substr(md5($time . $post['admin_user']), 0, 4); //随机4位密码盐

      global $uniqueSalt;
      $uniqueSalt = $salt;

      $password = $this->createPassword($post['admin_password'], $salt);

      // 超级管理员
      $sql = "INSERT INTO `la_admin`(`id`, `root`, `name`, `avatar`, `account`, `password`, `login_time`, `login_ip`, `multipoint_login`, `disable`, `create_time`, `update_time`, `delete_time`) VALUES (1, 1, '{$post['admin_user']}', '', '{$post['admin_user']}', '{$password}','{$time}', '', 1, 0, '{$time}', '{$time}', NULL);";
      // 超级管理员关联部门
      $sql .= "INSERT INTO `la_admin_dept` (`admin_id`, `dept_id`) VALUES (1, 1);";

      return $sql;
   }

   /**
    * Notes: 生成密码密文
    * @param $pwd
    * @param $salt
    * @return string
    */
   public function createPassword($pwd, $salt)
   {
      return md5($salt . md5($pwd . $salt));
   }



   /**
    * @notes 恢复admin,mobile index文件
    * @author 段誉
    * @date 2021/9/16 15:51
    */
   public function restoreIndexLock()
   {
      $this->checkIndexFile($this->getAppRoot() . '/public/mobile');
      $this->checkIndexFile($this->getAppRoot() . '/public/admin');
   }

   public function checkIndexFile($path)
   {
      if (file_exists($path . '/index_lock.html')) {
         // 删除提示文件
         unlink($path . '/index.html');
         // 恢复原入口
         rename($path . '/index_lock.html', $path . '/index.html');
      }
   }

   public function updateAuthSql($apiKey = "")
   {

      $params = [
         'api_key' => $apiKey == "" ? "--" : $apiKey,
      ];

      $params = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

      $sql = "UPDATE `{$this->prefix}config` SET value = '{$params}' WHERE type = 'model' AND name = 'key'";

      $this->dbh->query($sql);
   }

   public function updateVersionSql()
   {
      $projectVersion = $this->getProjectVersionConfig();

      $params = [
         'name'            => "Powered by IMAI.WORK",
         'version_name'    => $projectVersion['version_name'],
         'version_number'  => $projectVersion['version_number'],
         "install_time"    => date('Y-m-d H:i:s'),
         "update_time"     => date('Y-m-d H:i:s'),
      ];

      $params = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

      $sql = "UPDATE `{$this->prefix}config` SET value = '{$params}' WHERE type = 'website' AND name = 'version'";

      $this->dbh->query($sql);
   }

   private function getProjectVersionConfig(): array
   {
      $version       = '';
      $versionNumber = '';
      $configFile    = $this->getAppRoot() . '/config/project.php';

      if (is_file($configFile)) {
         $content = file_get_contents($configFile);

         if (preg_match("/'version'\\s*=>\\s*'([^']+)'/", $content, $matches)) {
            $version = $matches[1];
         }

         if (preg_match("/'version_number'\\s*=>\\s*'([^']+)'/", $content, $matches)) {
            $versionNumber = $matches[1];
         }
      }

      return [
         'version_name'   => $version,
         'version_number' => $versionNumber,
      ];
   }
}
