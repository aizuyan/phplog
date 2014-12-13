<?php
class Testlog{
	/**
	 * @var object $log 保存Log对象实例
	 */
	public static $log;

	/**
	 * @desc 测是开始
	 */
	public static function go(){
		/**
		 * @desc 包含日志容器和日志写入类库
		 */
		include_once("./log.php");
		/** 
		 * @desc 包含異常處理類文件
		 */
		require_once("./myexception.php");

		self::$log = Log::instance();
		self::$log->attach(new Logwriter("./data/debug"),Log::DEBUG);
		self::$log->attach(new Logwriter("./data/notice"),Log::NOTICE);

		set_exception_handler(array("Myexception","exceptionHandler"));
		set_error_handler(array("Myexception","errorHandler"));
		//设置一个程序异常终止的时候的错误处理函数
		register_shutdown_function(array("Myexception","shutdownHandler"));
	}
}
Testlog::go();
Testlog::$log->add(Log::STRACE,'jjjjyrtj',array('file'=>__FILE__,'line'=>__LINE__));
echo $b;
