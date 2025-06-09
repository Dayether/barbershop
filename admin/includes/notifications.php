<?php
/**
 * Create a session-based toast notification
 * 
 * @param string $type The type of toast (success, error, warning, info)
 * @param string $message The message to display
 * @param string $title The title of the toast (optional)
 */
function setToast($type, $message, $title = '') {
    $_SESSION['toast_type'] = $type;
    $_SESSION['toast_message'] = $message;
    $_SESSION['toast_title'] = $title;
}

/**
 * Set a success toast notification
 * 
 * @param string $message The success message
 * @param string $title The title (optional)
 */
function setSuccessToast($message, $title = 'Success') {
    setToast('success', $message, $title);
}

/**
 * Set an error toast notification
 * 
 * @param string $message The error message
 * @param string $title The title (optional)
 */
function setErrorToast($message, $title = 'Error') {
    setToast('error', $message, $title);
}

/**
 * Set a warning toast notification
 * 
 * @param string $message The warning message
 * @param string $title The title (optional)
 */
function setWarningToast($message, $title = 'Warning') {
    setToast('warning', $message, $title);
}

/**
 * Set an info toast notification
 * 
 * @param string $message The info message
 * @param string $title The title (optional)
 */
function setInfoToast($message, $title = 'Information') {
    setToast('info', $message, $title);
}
