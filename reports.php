<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: signin.html");
    exit();
}

// Validate inputs
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['start_date']) || empty($_POST['end_date'])) {
    http_response_code(400);
    echo "Invalid request.";
    exit();
}

$start_date = $_POST['start_date'];
$end_date   = $_POST['end_date'];

// Build query with prepared statements
$sql = "
SELECT
    o.id AS order_id,
    o.created_at,
    o.total AS order_total,
    o.status,
    u.full_name,
    u.email,
    oi.product_name,
    oi.unit_price,
    oi.qty,
    (oi.unit_price * oi.qty) AS line_total
FROM orders o
JOIN users u ON o.user_id = u.id
JOIN order_items oi ON o.id = oi.order_id
WHERE DATE(o.created_at) BETWEEN ? AND ?
ORDER BY o.created_at DESC, o.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$res = $stmt->get_result();

// CSV export
if (isset($_POST['generate_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales_report_' . $start_date . '_to_' . $end_date . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ["Order ID","Date","Customer","Email","Product","Unit Price","Quantity","Line Total","Order Total","Status"]);

    while ($row = $res->fetch_assoc()) {
        fputcsv($out, [
            $row['order_id'],
            $row['created_at'],
            $row['full_name'],
            $row['email'],
            $row['product_name'],
            number_format((float)$row['unit_price'], 2, '.', ''),
            (int)$row['qty'],
            number_format((float)$row['line_total'], 2, '.', ''),
            number_format((float)$row['order_total'], 2, '.', ''),
            ucfirst($row['status'])
        ]);
    }
    fclose($out);
    exit();
}

// PDF export
if (isset($_POST['generate_pdf'])) {
    // Ensure FPDF is installed: place the `fpdf` folder in your project root
    // and keep this path as-is.
    require_once __DIR__ . '/fpdf/fpdf.php';

    $pdf = new FPDF('L', 'mm', 'A4'); // Landscape for more columns
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10,"Sales Report ($start_date to $end_date)",0,1,'C');
    $pdf->Ln(2);

    // Header
    $pdf->SetFont('Arial','B',9);
    $headers = ['Order ID','Date','Customer','Email','Product','Unit Price','Qty','Line Total','Order Total','Status'];
    $widths  = [20, 28, 35, 45, 50, 25, 12, 25, 25, 20]; // adjust to fit A4 landscape
    for ($i=0; $i<count($headers); $i++) {
        $pdf->Cell($widths[$i], 8, $headers[$i], 1, 0, 'C');
    }
    $pdf->Ln();

    // Rows
    $pdf->SetFont('Arial','',8);
    $res->data_seek(0);
    while ($row = $res->fetch_assoc()) {
        $pdf->Cell($widths[0], 7, $row['order_id'], 1);
        $pdf->Cell($widths[1], 7, substr($row['created_at'], 0, 16), 1);
        $pdf->Cell($widths[2], 7, mb_strimwidth($row['full_name'], 0, 26, '…', 'UTF-8'), 1);
        $pdf->Cell($widths[3], 7, mb_strimwidth($row['email'], 0, 36, '…', 'UTF-8'), 1);
        $pdf->Cell($widths[4], 7, mb_strimwidth($row['product_name'], 0, 44, '…', 'UTF-8'), 1);
        $pdf->Cell($widths[5], 7, number_format((float)$row['unit_price'], 2), 1, 0, 'R');
        $pdf->Cell($widths[6], 7, (int)$row['qty'], 1, 0, 'R');
        $pdf->Cell($widths[7], 7, number_format((float)$row['line_total'], 2), 1, 0, 'R');
        $pdf->Cell($widths[8], 7, number_format((float)$row['order_total'], 2), 1, 0, 'R');
        $pdf->Cell($widths[9], 7, ucfirst($row['status']), 1);
        $pdf->Ln();
    }

    $pdf->Output('D', "sales_report_{$start_date}_to_{$end_date}.pdf");
    exit();
}

// If neither button matched:
http_response_code(400);
echo "No export action provided.";
