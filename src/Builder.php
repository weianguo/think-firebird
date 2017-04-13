<?php
// +----------------------------------------------------------------------
// | Firebird driver for thinkphp5
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: weianguo <366958903@qq.com>
// +----------------------------------------------------------------------

namespace think\firebird;

use think\db\Builder as BaseBuilder;

/**
 * Firebird数据库驱动
 */
class Builder extends BaseBuilder
{

    protected $selectSql = 'SELECT %LIMIT% %DISTINCT% %FIELD% FROM %TABLE%%FORCE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER% %UNION%%LOCK%%COMMENT%';
	
    /**
     * limit分析
     * @access protected
     * @param mixed $limit
     * @return string
     */
    public function parseLimit($limit)
    {
        $limitStr = '';
        if (!empty($limit)) {
            $limit = explode(',', $limit);
            if (count($limit) > 1) {
                $limitStr = ' FIRST ' . $limit[1] . ' SKIP ' . $limit[0] . ' ';
            } else {
                $limitStr = ' FIRST ' . $limit[0] . ' ';
            }
        }
        return $limitStr;
    }
	
    /**
     * 随机排序
     * @access protected
     * @return string
     */
    protected function parseRand()
    {
        return 'rand()';
    }

}
