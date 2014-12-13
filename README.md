phplog
======

php日志系统

	用来记录系统运行中的日志，错误、异常等信息，使用方法简单

包含文件、设置日志容器，以及写入实例、设置异常处理函数

/**
 * @desc 包含日志容器和日志写入类库
 */
include_once("./log.php");

/** 
 * @desc 包含异常处理文件
 */

require_once("./myexception.php");

//获取日志实例，并添加写入日志实例
self::$log = Log::instance();
self::$log->attach(new Logwriter("./data/debug"),Log::DEBUG);
self::$log->attach(new Logwriter("./data/notice"),Log::NOTICE);

//设置异常处理函数
set_exception_handler(array("Myexception","exceptionHandler"));
set_error_handler(array("Myexception","errorHandler"));
register_shutdown_function(array("Myexception","shutdownHandler"));


