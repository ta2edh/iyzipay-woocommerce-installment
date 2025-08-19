<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * iyzico Installment Logger
 */
class Iyzico_Installment_Logger
{
    /**
     * Log file path
     *
     * @var string
     */
    private $log_file;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->log_file = IYZI_INSTALLMENT_PATH . '/logs/debug.log';
    }

    /**
     * Log message
     *
     * @param string $message Message to log.
     * @param string $level Log level (info, warning, error).
     */
    public function log($message, $level = 'info')
    {
        // Ensure log directory exists
        $this->ensure_log_directory_exists();

        // Format message
        $timestamp = wp_date('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

        // Write to log file
        file_put_contents($this->log_file, $log_message, FILE_APPEND);
    }

    /**
     * Log info message
     *
     * @param string $message Message to log.
     */
    public function info($message)
    {
        $this->log($message, 'INFO');
    }

    /**
     * Log warning message
     *
     * @param string $message Message to log.
     */
    public function warning($message)
    {
        $this->log($message, 'WARNING');
    }

    /**
     * Log error message
     *
     * @param string $message Message to log.
     */
    public function error($message)
    {
        $this->log($message, 'ERROR');
    }

    /**
     * Log exception
     *
     * @param Exception $exception Exception.
     */
    public function exception($exception)
    {
        $message = "Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
        $trace = $exception->getTraceAsString();
        $this->error($message . PHP_EOL . $trace);
    }

    /**
     * Ensure log directory exists
     */
    private function ensure_log_directory_exists()
    {
        global $wp_filesystem;
    
        // Initialize WP_Filesystem if not already done
        if (empty($wp_filesystem)) {
            require_once(ABSPATH . '/wp-admin/includes/file.php');
            WP_Filesystem();
        }
    
        $log_dir = dirname($this->log_file);
    
        if (!$wp_filesystem->is_dir($log_dir)) {
            $wp_filesystem->mkdir($log_dir, FS_CHMOD_DIR);
        }
    }

    /**
     * Get log file content
     *
     * @return string
     */
    public function get_log_content()
    {
        if (file_exists($this->log_file)) {
            return file_get_contents($this->log_file);
        }
        
        return '';
    }

    /**
     * Clear log file
     */
    public function clear_log()
    {
        if (file_exists($this->log_file)) {
            file_put_contents($this->log_file, '');
        }
    }

    /**
     * Check if log file exists
     *
     * @return bool
     */
    public function log_file_exists()
    {
        return file_exists($this->log_file);
    }

    /**
     * Check if log file is writable
     *
     * @return bool
     */
    public function is_log_file_writable()
    {
        global $wp_filesystem;
        
        // Initialize WP_Filesystem
        if (empty($wp_filesystem)) {
            require_once(ABSPATH . '/wp-admin/includes/file.php');
            
            $credentials = request_filesystem_credentials('', '', false, false, null);
            if (!WP_Filesystem($credentials)) {
                // Return false if WP_Filesystem fails to initialize
                return false;
            }
        }
        
        // Check if file exists and is writable
        if ($wp_filesystem->exists($this->log_file)) {
            return $wp_filesystem->is_writable($this->log_file);
        }
        
        // If file doesn't exist, check if directory is writable
        $log_dir = dirname($this->log_file);
        if ($wp_filesystem->exists($log_dir)) {
            return $wp_filesystem->is_writable($log_dir);
        }
        
        return false;
    }
} 