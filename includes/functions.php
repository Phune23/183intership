<?php
/**
 * Set a message in session to be displayed on next page load
 * 
 * @param string $type The message type (success, error, info, warning)
 * @param string $message The message text
 * @return void
 */
function set_message($type, $message) {
    $_SESSION[$type] = $message;
}

/**
 * Display messages that were set using set_message()
 * 
 * @return void
 */
function display_messages() {
    $message_types = ['success', 'error', 'info', 'warning'];
    
    foreach ($message_types as $type) {
        if (isset($_SESSION[$type])) {
            echo '<div class="message ' . $type . '">' . htmlspecialchars($_SESSION[$type]) . '</div>';
            unset($_SESSION[$type]);
        }
    }
}

/**
 * Validate email address format
 * 
 * @param string $email Email address to validate
 * @return boolean True if valid, false otherwise
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Convert date to MySQL format (YYYY-MM-DD)
 * 
 * @param string $date Date string
 * @return string MySQL formatted date
 */
function format_date_for_mysql($date) {
    if (empty($date)) return null;
    $timestamp = strtotime($date);
    return $timestamp ? date('Y-m-d', $timestamp) : null;
}

/**
 * Format date from MySQL to display format (DD/MM/YYYY)
 * 
 * @param string $mysql_date MySQL date (YYYY-MM-DD)
 * @return string Formatted date (DD/MM/YYYY)
 */
function format_date_for_display($mysql_date) {
    if (empty($mysql_date)) return '';
    $timestamp = strtotime($mysql_date);
    return $timestamp ? date('d/m/Y', $timestamp) : '';
}
?>