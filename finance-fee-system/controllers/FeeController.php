<?php
require_once '../../config/database.php';
require_once '../../models/Fee.php';
require_once '../../utils/response.php';

class FeeController {
    private $db;
    private $fee;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->fee = new Fee($this->db);
    }

    public function getFees() {
        $stmt = $this->fee->readAll();
        $num = $stmt->rowCount();

        if($num > 0) {
            $fees_arr = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                array_push($fees_arr, $row);
            }
            sendJsonResponse(200, true, "Fees retrieved successfully", $fees_arr);
        } else {
            sendJsonResponse(404, false, "No fees found.");
        }
    }
}
?>