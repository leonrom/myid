<?php
	
	/* Finally, A light, permissions-checking logging class. 
	 * 
	 * Author	: Kenneth Katzgrau < katzgrau@gmail.com >
	 * Date	: July 26, 2008
	 * Comments	: Originally written for use with wpSearch
	 * Website	: http://codefury.net
	 * Version	: 1.0
	 *
	 * Usage: 
	 *		$log = new KLogger ( "log.txt" , KLogger::INFO, dfmt );
	 *		$log->LogInfo("Returned a million search results");	//Prints to the log file
	 *		$log->LogFATAL("Oh dear.");				//Prints to the log file
	 *		$log->LogDebug("x = 5");					//Prints nothing due to priority setting
	*/
	
	class KLogger
	{
		const INFO 		= 1;	// Most Verbose
		const DEBUG 	= 2;	// ...
		const WARN 		= 3;	// ...
		const ERROR 	= 4;	// ...
		const FATAL 	= 5;	// Least Verbose
		const OFF 		= 6;	// Nothing at all.
		
		const LOG_OPEN 		= 1;
		const OPEN_FAILED 	= 2;
		const LOG_CLOSED 	= 3;
		
		/* Public members: Not so much of an example of encapsulation, but that's okay. */
		public $Log_Status 	= self::LOG_CLOSED;
		public $DateFormat	= "Y-m-d G:i:s";
		public $MessageQueue;
	
		private $log_file;
		private $priority = self::INFO;
		
		private $file_handle;
		
		public function __construct( $filepath , $priority, $dfmt )
		{
			if ( $priority == self::OFF ) return;
			
			$this->log_file = $filepath;
			$this->MessageQueue = array();
			$this->priority = $priority;
			if ($dfmt){
				$this->DateFormat = $dfmt;
			}
			
			if ( file_exists( $this->log_file ) )
			{
				if ( !is_writable($this->log_file) )
				{
					$this->Log_Status = self::OPEN_FAILED;
					$this->MessageQueue[] = "The file exists, but could not be opened for writing. Check that appropriate permissions have been set.";
					return;
				}
			}
			
			if ( $this->file_handle = fopen( $this->log_file , "w" ) )
			{
				$this->Log_Status = self::LOG_OPEN;
				$this->MessageQueue[] = "The log file was opened successfully.";
			}
			else
			{
				$this->Log_Status = self::OPEN_FAILED;
				$this->MessageQueue[] = "The file could not be opened. Check permissions.";
			}
			
			return;
		}
		
		public function __destruct()
		{
			if ( $this->file_handle )
				fclose( $this->file_handle );
		}
		
		public function LogInfo($line)
		{
			$this->Log( $line , self::INFO );
		}
		
		public function LogDebug($line)
		{
			$this->Log( $line , self::DEBUG );
		}
		
		public function LogWarn($line)
		{
			$this->Log( $line , self::WARN );	
		}
		
		public function LogError($line)
		{
			$this->Log( $line , self::ERROR );		
		}

		public function LogFatal($line)
		{
			$this->Log( $line , self::FATAL );
		}
		
		public function Log($line, $priority)
		{
			if ( $this->priority <= $priority )
			{
				$status = $this->getTimeLine( $priority );
				$this->WriteFreeFormLine ( "$status $line \n" );
			}
		}
		
		public function WriteFreeFormLine( $line )
		{
			if ( $this->Log_Status == self::LOG_OPEN && $this->priority != self::OFF )
			{
			    if (fwrite( $this->file_handle , $line ) === false) {
			        $this->MessageQueue[] = "The file could not be written to. Check that appropriate permissions have been set.";
			    }
			}
		}
		
		private function getTimeLine( $level )
		{
			$time = date( $this->DateFormat );
			$s = '';
// не нужно	if ($level >= $priority)
			{
				switch( $level )
				{
				case self::INFO:
					$s= "$time INFO .";
				    break;
				case self::DEBUG:
					$s= "$time DEBUG.";				
				    break;
				case self::WARN:
					$s= "$time WARN .";				
				    break;
				case self::ERROR:
					$s= "$time ERROR.";
				    break;
				case self::FATAL:
					$s= "$time FATAL.";
				    break;
				default:
					$s= "$time($level) .";
				}
			}
			if (strlen($s) > 0)
			{
				$callers=debug_backtrace();
				foreach(array_reverse($callers) as $call) {
					$file = basename($call['file'], ".php");
					if (strcmp($file, 'KLogger') !== 0) {
    					$s .= '/' . $file . '.' . $call['line'];
					}
				}
				$L = strlen($s);
				$m = 40;
				for ($i = 1; $i <= 10; $i++) {
					if ($L < $m) return str_pad( $s, $m);
					$m += 10;
				}
				if ($L < 40) return str_pad( $s, 40);
				if ($L < 60) return str_pad( $s, 60);
				if ($L < 80) return str_pad( $s, 80);
			}
			return $s;
		}
		
	}


?>