<?php
/**
 * 日志写入类
 * @author 	AizuYan
 * @email 	luluyrt@163.com
 */
class Log {
	// 日志消息等级
	const EMERGENCY = LOG_EMERG;    // 0
	const ALERT     = LOG_ALERT;    // 1
	const CRITICAL  = LOG_CRIT;     // 2
	const ERROR     = LOG_ERR;      // 3
	const WARNING   = LOG_WARNING;  // 4
	const NOTICE    = LOG_NOTICE;   // 5
	const INFO      = LOG_INFO;     // 6
	const STRACE    = 7;
	const DEBUG     = 8;
	/**
	 * @var  string  日志记录时间的格式
	 */
	public static $timestamp = 'Y-m-d H:i:s';
	/**
	 * @var  boolean  是否在运行过程中马上记录的标志
	 */
	public static $writeOnAdd = false;
	/**
	 * @var  Log 单例模式的容器
	 */
	protected static $_instance = null;
	/**
	 * 获取单例模式，并将Log::write方法加入脚本停止时执行的函数列表
	 *
	 *     $log = Log::instance();
	 *
	 * @return  Log
	 */
	public static function instance() {
		if (Log::$_instance === null) {
			Log::$_instance = new Log;
 			register_shutdown_function(array(Log::$_instance, 'write'));
		}
		return Log::$_instance;
	}

	/**
	 * @var  array  日志消息数组
	 */
	protected $_messages = array();
	
	/**
	 * @var  array  保存写日志(Logwriter)对象的数组
	*/
	protected $_writers = array();

	/**
	 * 添加一个写日志对象到日志对象(Log)中，并设置该写日志对象记录哪些错误等级
	 *
	 *     $log->attach($writer);
	 *
	 * @param   object   写日志对象
	 * @param   mixed    写日志对象要记录的错误的等级数组，或者要记录等最大等级
	 * @param   integer  如果前面的$levels不是数组，这个参数有效，表示最小的记录等级
	 * @return  Log
	*/
	public function attach(Logwriter $writer, $levels = array(), $min_level = 0) {
		if ( ! is_array($levels)) {
			$levels = range($min_level, $levels);
		}
		//将写日志对象和该对象要记录的等级存入日志对象中
		$this->_writers["{$writer}"] = array (
				'object' => $writer,
				'levels' => $levels
		);
		return $this;
	}

	/**
	 * 从日志对象中去除一个写日志对象. The same writer object must be used.
	 *
	 *     $log->detach($writer);
	 *
	 * @param   object  写日志(Log_Writer)实例
	 * @return  Log
	 */
	public function detach(Logger $writer) {
		// 移除一个“写日志”对象
		unset($this->_writers["{$writer}"]);
		return $this;
	}

	/**
	 * 添加一组日志信息到日志对象中
	 *
	 *     $log->add(Log::ERROR, 'Could not locate user: :user', array(
	 *         ':user' => $username,
	 *     ));
	 *
	 * @param   string  这组日志对象的错误等级
	 * @param   string  日志消息
	 * @param 	array 	记录错误位置信息
	 * 
	 * 		array('file'=>__FILE__,'line'=>'__LINE__');
	 *
	 * @return  Log
	 */
	public function add($level, $message, array $additional=null) {
		// Create a new message and timestamp it
		$this->_messages[] = array (
				'time'  => date(Log::$timestamp, time()),
				'level' => $level,
				'body'  => $message,
				'file'	=> isset($additional['file']) ? $additional['file'] : NULL,
				'line'	=> isset($additional['line']) ? $additional['line'] : NULL,
		);
		if (Log::$writeOnAdd) {
			$this->write();
		}
		return $this;
	}
	
	/**
	 * 记录并清理所有的日志信息
	 *
	 *     $log->write();
	 *
	 * @return  无
	 */
	public function write() {
		if (empty($this->_messages)) {
			// 无日志消息返回
			return;
		}
		// 将消息保存至私有变量中
		$messages = $this->_messages;
		// 清空消息数组
		$this->_messages = array();
		foreach ($this->_writers as $writer) {
			if (empty($writer['levels'])) {
				// 如果该“写日志”对象的levels数组为空，该“写日志”对象记录所有级别的日志
				$writer['object']->write($messages);
			} else {
				// 对消息进行过滤，记录需要改Logwriter记录的日志信息到数组$filtered
				$filtered = array();
				foreach ($messages as $message) {
					if (in_array($message['level'], $writer['levels'])) {
						$filtered[] = $message;
					}
				}
				// 写入过滤后的日志到该Logwriter对象的目录
				$writer['object']->write($filtered);
			}
		}
	}
}

/**
 * 系统日志记录执行记录操作的类库，并把他们存储为/YYYY/MM/DD.php格式
 */
class Logwriter {
	const FILE_EXT = '.php';
	//安全信息，用于获取日志时的验证（我这里是“欢迎来到日志的世界”）
	const FILE_SECURITY = '<?php ($_GET[\'p\'] && md5($_GET[\'p\']) == \'8b812956650da4ed2199a4463f55194b\') or die(\'No direct script access.\');';
	/**
	 * @var  string  保存日志的目录
	 */
	protected $_directory;

	/**
	 * 创建一个新的日志写操作实例
	 *
	 *     $writer = new Logwriter($directory);
	 *
	 * @param   string  当前实例存储日志的目录
	 * @return  无
	 */
	public function __construct($directory) {
		if (!is_dir($directory) || !is_writable($directory)) {
			try{
				mkdir($directory,true);
				chmod($directory, 0777);
			}catch(Exception $e){

			}
		}
		// 将保存日志的目录路径放入对象环境中
		$this->_directory = realpath($directory).DIRECTORY_SEPARATOR;
	}

	/**
	 * 将messages数组中的每一组日志信息存储到文件中，格式为/YYYY/MM/DD.php 
	 * example:2014/11/18.php 表示2014年11月18日的日志文件
	 *
	 *     $writer->write($messages);
	 *
	 * @param   array   要保存的日志信息
	 * @return  void
	 */
	public function write(array $messages) {
		// “年”这一级目录
		$directory = $this->_directory.date('Y');
		if ( ! is_dir($directory)) {
			// 如果“年”级目录不存在，创建
			mkdir($directory, 02777);
			// 设置目录权限(must be manually set to fix umask issues)
			chmod($directory, 02777);
		}

		// “月”这一级目录
		$directory .= DIRECTORY_SEPARATOR.date('m');
		if ( ! is_dir($directory)) {
			// 如果“月”级目录不存在，创建
			mkdir($directory, 02777);
			// 设置权限 (must be manually set to fix umask issues)
			chmod($directory, 02777);
		}

		// 要写入的文件
		$filename = $directory.DIRECTORY_SEPARATOR.date('d').self::FILE_EXT;
		if ( ! file_exists($filename)) {
			// 如果不存在日志文件，创建，并在记录日志开始写入安全验证程序
			file_put_contents($filename, self::FILE_SECURITY.' ?>'.PHP_EOL);
			// 设置文件权限为所有用户可读可写
			chmod($filename, 0666);
		}

		foreach ($messages as $message) {
			// 循环日志写信数组，写入每一条日志
			file_put_contents($filename, PHP_EOL.$message['time'].' --- '.$message['level'].': '.$message['body'].'		[at file]:'.$message['file'].'		[at line]:'.$message['line'], FILE_APPEND);
		}
	}

	/** 
	 * 魔术方法，生成对象的唯一标识
	 *
	 * @return void
	 */
	public function __toString() {
		return spl_object_hash($this);
	}
}
