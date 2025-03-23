<?php
/**
 * Get configuration value from environment or return default
 * 
 * @param string $name Environment variable name
 * @param mixed $default Default value if not found
 * @return mixed Value from environment or default
 */
function config($name, $default = null) {
    // Check multiple environment variable names
    $names = is_array($name) ? $name : [$name];
    
    foreach ($names as $varName) {
        if (getenv($varName) !== false) {
            return getenv($varName);
        }
    }
    
    return $default;
}
?>