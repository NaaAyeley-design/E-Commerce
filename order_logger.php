<?php
/**
 * Order Logger - Logs order creation attempts and errors
 */
class OrderLogger {
    private static $logFile = 'order_errors.log';
    
    public static function log($message, $data = null) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message";
        
        if ($data !== null) {
            $logMessage .= "\nData: " . print_r($data, true);
        }
        
        $logMessage .= "\n" . str_repeat('-', 80) . "\n";
        
        // Log to file
        error_log($logMessage, 3, self::$logFile);
        
        // Also log to PHP error log
        error_log($message);
    }
    
    public static function logError($message, $exception = null) {
        $logMessage = "ERROR: $message";
        
        if ($exception instanceof Exception) {
            $logMessage .= "\nException: " . $exception->getMessage();
            $logMessage .= "\nTrace: " . $exception->getTraceAsString();
        }
        
        self::log($logMessage);
    }
    
    public static function logSuccess($message, $data = null) {
        self::log("SUCCESS: $message", $data);
    }
}
?>

