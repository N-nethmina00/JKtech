<?php
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,"UTF-8");}
function start_session(){if(session_status()===PHP_SESSION_NONE)session_start();}
function require_login(){start_session();if(empty($_SESSION["user"])){header("Location: login.php");exit;}}
function require_admin(){require_login();if(($_SESSION["user"]["role"]??"")!=="admin"){http_response_code(403);exit("Forbidden");}}
function cart_count(){start_session();return array_sum($_SESSION["cart"]??[]);}
