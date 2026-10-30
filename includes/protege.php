<?php
session_start();

if (!isset($_SESSION["funcionario_id"])) {
    header("Location: /login.php");
    exit;
}