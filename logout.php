<?php

session_start();

if (isset($_SESSION['user_id'])) {
	unset($_SESSION['user_id']);
}

$gc_knowledge = file_get_contents('gc.txt');

header("Location: login.php");
die;
