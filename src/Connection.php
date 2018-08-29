<?php
// +----------------------------------------------------------------------
// | Firebird driver for thinkphp5
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: weianguo <366958903@qq.com>
// +----------------------------------------------------------------------

namespace think\firebird;

use PDO;
use think\Db;
use think\db\Connection as BaseConnection;
use think\db\Query;
use think\firebird\Builder;

/**
 * Firebird数据库驱动
 */
class Connection extends BaseConnection
{
    protected $builder = '\\think\\firebird\\builder';

    /**
     * 架构函数 读取数据库配置信息
     * @access public
     * @param array $config 数据库配置数组
     */
    public function __construct(array $config = [])
    {

        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }
        $this->builder = new Builder($this);
    }

    /**
     * 解析pdo连接的dsn信息
     * @access public
     * @param array $config 连接信息
     * @return string
     */
    protected function parseDsn($config)
    {
        $dsn = 'firebird:dbname=' . $config['hostname'] . '/' . $config['hostport'] . ':' . $config['database'];
        return $dsn;
    }

    /**
     * 取得数据表的字段信息
     * @access public
     * @param string $tableName
     * @return array
     */
    public function getFields($tableName)
    {
        list($tableName) = explode(' ', $tableName);
        $sql = 'SELECT TRIM(RF.RDB$FIELD_NAME) AS FIELD,RF.RDB$DEFAULT_VALUE AS DEFAULT1,RF.RDB$NULL_FLAG AS NULL1,TRIM(T.RDB$TYPE_NAME) || \'(\' || F.RDB$FIELD_LENGTH || \')\' as TYPE FROM RDB$RELATION_FIELDS RF LEFT JOIN RDB$FIELDS F ON (F.RDB$FIELD_NAME = RF.RDB$FIELD_SOURCE) LEFT JOIN RDB$TYPES T ON (T.RDB$TYPE = F.RDB$FIELD_TYPE) WHERE RDB$RELATION_NAME=UPPER(\'' . $tableName . '\') AND T.RDB$FIELD_NAME = \'RDB$FIELD_TYPE\' ORDER By RDB$FIELD_POSITION';
        $result = $this->query($sql, [], true, true);
        $info = [];
        if ($result) {
            foreach ($result as $key => $val) {
                $info[$val[0]] = [
                    'name' => $val[0],
                    'type' => $val[3],
                    'notnull' => ($val[2] == 1),
                    'default' => $val[1],
                    'primary' => false,
                    'autoinc' => false,
                ];
            }
        }
        //获取主键
        $sql = 'select TRIM(b.rdb$field_name) as field_name from rdb$relation_constraints a join rdb$index_segments b on a.rdb$index_name=b.rdb$index_name where a.rdb$constraint_type=\'PRIMARY KEY\' and a.rdb$relation_name=UPPER(\'' . $tableName . '\')';
        $rs_temp = $this->query($sql, [], true, true);
        foreach ($rs_temp as $row) {
            $info[$row[0]]['primary'] = true;
        }
        return $this->fieldCase($info);
    }

    /**
     * 取得数据库的表信息
     * @access public
     * @param string $dbName
     * @return array
     */

    public function getTables($dbName = '')
    {
        $sql = 'SELECT DISTINCT RDB$RELATION_NAME FROM RDB$RELATION_FIELDS WHERE RDB$SYSTEM_FLAG=0';
        $result = $this->query($sql);
        $info = [];
        foreach ($result as $key => $val) {
            $info[$key] = trim(current($val));
        }
        return $info;
    }

    /**
     * 启动事务
     * @access public
     * @return bool|null
     */
    public function startTrans()
    {
        $this->initConnect(true);
        if (!$this->linkID) {
            return false;
        }

        ++$this->transTimes;

        if (1 == $this->transTimes) {
            $this->linkID->setAttribute(\PDO::ATTR_AUTOCOMMIT, false);
            $this->linkID->beginTransaction();
        } elseif ($this->transTimes > 1 && $this->supportSavepoint()) {
            $this->linkID->exec(
                $this->parseSavepoint('trans' . $this->transTimes)
            );
        }
    }

    /**
     * SQL性能分析
     * @access protected
     * @param string $sql
     * @return array
     */
    protected function getExplain($sql)
    {
        return [];
    }

    /**
     * 获取最近插入的ID
     * @access public
     * @param  string  $sequence     自增序列名
     * @return string
     */
    public function getLastInsID($sequence = null)
    {
        return true;
    }
}
