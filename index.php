<?php
/**
 * Main Entry Point Redirect
 * Mengarahkan traffic dari root folder ke folder public
 */

// Lakukan redirect ke folder public
header('Location: public/');
exit;