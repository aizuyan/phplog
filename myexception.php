<?php
/** 
 * 异常处理类
 * @author 	燕睿涛
 * @email 	luluyrt@163.com
 */
class Myexception extends Exception{
	/**
	 * @desc 	异常处理函数
	 * @parm 	object $e 异常对象
	 */
	public static function exceptionHandler($e){
		$file 	= $e->getFile();
		$line 	= $e->getLine();
		$code 	= $e->getCode();
		$message= $e->getMessage();
		if(Testlog::$log != null){
			if(class_exists('Log',false)){
				Log::$writeOnAdd = true;
				Testlog::$log->add(3,'['.$code.']:'.$message,array('file'=>$file,'line'=>$line));
			}
		}
	}

	/**
	 * @desc 	错误处理函数
	 *
	 */
	public static function errorHandler($errno,$errstr,$errfile,$errline){
		self::exceptionHandler(new ErrorException($errstr,$errno,0,$errfile,$errline));
	}

	/**
	 *
	 *
	 */
	public static function shutdownHandler(){
		$error = error_get_last();
		if($error){
			self::exceptionHandler(new ErrorException($error['message'],$error['type'],0,$error['file'],$error['line']));
		}
	}
}