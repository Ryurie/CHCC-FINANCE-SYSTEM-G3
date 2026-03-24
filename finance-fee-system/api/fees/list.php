<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

require_once '../../controllers/FeeController.php';

$controller = new FeeController();
$controller->getFees();
?>