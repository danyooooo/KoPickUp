<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['manager', 'admin'])) {
    die('Access Denied');
}

$format = $_GET['format'] ?? 'csv';

$parcels = $pdo->query(
    "SELECT p.*, COALESCE(u.fullname, p.recipient_name) as recipient 
     FROM parcels p 
     LEFT JOIN users u ON p.user_id = u.id 
     ORDER BY recipient ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$filename = "KoPickUp_Export_" . date('Y-m-d') . "." . $format;

switch ($format) {
    case 'csv':
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '";');
        
        $output = fopen('php://output', 'w');
        
        fputcsv($output, ['Recipient Name', 'Tracking Number', 'Courier', 'Status', 'Date Registered', 'Date Collected']);
        
        foreach ($parcels as $parcel) {
            $registered_date = $parcel['registered_at'] ? date('Y-m-d H:i:s', strtotime($parcel['registered_at'])) : '';
            $collected_date = $parcel['collected_at'] ? date('Y-m-d H:i:s', strtotime($parcel['collected_at'])) : '';
            
            fputcsv($output, [
                $parcel['recipient'],
                $parcel['tracking_number'],
                $parcel['courier'],
                $parcel['status'],
                $registered_date,
                $collected_date
            ]);
        }
        
        fclose($output);
        break;

    case 'pdf':
        require('../fpdf/fpdf.php');

        $pdf = new FPDF('L', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 12);

        $pdf->Cell(50, 10, 'Recipient Name', 1);
        $pdf->Cell(50, 10, 'Tracking Number', 1);
        $pdf->Cell(30, 10, 'Courier', 1);
        $pdf->Cell(35, 10, 'Status', 1);
        $pdf->Cell(50, 10, 'Date Registered', 1);
        $pdf->Cell(50, 10, 'Date Collected', 1);
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 10);

        foreach ($parcels as $parcel) {
            $pdf->Cell(50, 10, $parcel['recipient'], 1);
            $pdf->Cell(50, 10, $parcel['tracking_number'], 1);
            $pdf->Cell(30, 10, $parcel['courier'], 1);
            $pdf->Cell(35, 10, $parcel['status'], 1);
            $pdf->Cell(50, 10, $parcel['registered_at'] ? date('Y-m-d H:i', strtotime($parcel['registered_at'])) : '', 1);
            $pdf->Cell(50, 10, $parcel['collected_at'] ? date('Y-m-d H:i', strtotime($parcel['collected_at'])) : '', 1);
            $pdf->Ln();
        }

        $pdf->Output('D', $filename);
        break;

    case 'excel':
        $filename = "KoPickUp_Export_" . date('Y-m-d') . ".xls";

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        echo '<table border="1">';
        echo '<tr>';
        echo '<th>Recipient Name</th>';
        echo '<th>Tracking Number</th>';
        echo '<th>Courier</th>';
        echo '<th>Status</th>';
        echo '<th>Date Registered</th>';
        echo '<th>Date Collected</th>';
        echo '</tr>';

        foreach ($parcels as $parcel) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($parcel['recipient']) . '</td>';
            echo '<td>' . htmlspecialchars($parcel['tracking_number']) . '</td>';
            echo '<td>' . htmlspecialchars($parcel['courier']) . '</td>';
            echo '<td>' . htmlspecialchars($parcel['status']) . '</td>';
            echo '<td>' . ($parcel['registered_at'] ? date('Y-m-d H:i:s', strtotime($parcel['registered_at'])) : '') . '</td>';
            echo '<td>' . ($parcel['collected_at'] ? date('Y-m-d H:i:s', strtotime($parcel['collected_at'])) : '') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        break;
}

exit;
?>