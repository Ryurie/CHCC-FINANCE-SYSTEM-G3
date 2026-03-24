<?php
// controllers/InvoiceController.php
require_once dirname(__DIR__) . '/models/Invoice.php';

class InvoiceController {
    private $db;
    private $invoiceModel;

    public function __construct($db) {
        $this->db = $db;
        $this->invoiceModel = new Invoice($db);
    }

    public function listInvoices($userId, $role) {
        $invoices = $this->invoiceModel->getInvoices($userId, $role);

        if ($invoices) {
            return [
                "success" => true,
                "count" => count($invoices),
                "data" => $invoices
            ];
        }

        return [
            "success" => true,
            "count" => 0,
            "data" => [],
            "message" => "No invoices found."
        ];
    }
}
?>