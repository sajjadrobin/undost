<?php
/**
 * Created by PhpStorm.
 * User: saro
 * Date: 11/05/15
 * Time: 16:47
 */
if(!isset($_SESSION)) {
    session_start();
}

session_destroy();
header("Location:index.php");
die();