<?php

namespace App\Services;

use TCPDF;

class PdfService
{
    public function generateEquipmentHistorySheet($equipment, $serial, $location, $historyData)
    {
        // Create new PDF document
        $pdf = new class(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false) extends TCPDF {
            // Page header
            public function Header() {
                // University logo
                $logo = public_path('images/usep_logo.png');
                if (file_exists($logo)) {
                    $this->Image($logo, 15, 8, 25, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
                }

                // University name & address (centered)
                $this->SetFont('helvetica', 'B', 12);
                $this->SetY(10);
                $this->Cell(0, 0, 'Republic of the Philippines', 0, 1, 'C');
                $this->Ln(2);
                $this->Cell(0, 0, 'University of Southeastern Philippines', 0, 1, 'C');
                $this->SetFont('helvetica', '', 10);
                $this->Cell(0, 0, 'Iñigo St., Bo. Obrero, Davao City 8000', 0, 1, 'C');
                $this->Cell(0, 0, 'Telephone: (082) 227-8192', 0, 1, 'C');
                $this->Cell(0, 0, 'Website: www.usep.edu.ph', 0, 1, 'C');
                $this->Cell(0, 0, 'Email: president@usep.edu.ph', 0, 1, 'C');

                // Form metadata (top-right)
                $this->SetFont('helvetica', '', 9);
                $this->SetXY(150, 10);
                $this->Cell(40, 5, 'Form No. FM-USeP-ICT-04', 0, 0, 'R');
                $this->Ln(4);
                $this->SetX(150);
                $this->Cell(40, 5, 'Issue Status 01', 0, 0, 'R');
                $this->Ln(4);
                $this->SetX(150);
                $this->Cell(40, 5, 'Revision No. 00', 0, 0, 'R');
                $this->Ln(4);
                $this->SetX(150);
                $this->Cell(40, 5, 'Date Effective 23 December 2022', 0, 0, 'R');
                $this->Ln(4);
                $this->SetX(150);
                $this->Cell(40, 5, 'Approved by President', 0, 0, 'R');

                // Title
                $this->SetY(40);
                $this->SetFont('helvetica', 'B', 14);
                $this->Cell(0, 10, 'ICT EQUIPMENT HISTORY SHEET', 0, 1, 'C');
            }

            // Page footer
            public function Footer() {
                $this->SetY(-15);
                $this->SetFont('helvetica', 'I', 8);
                $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C');
            }
        };

        // Set document information
        $pdf->SetCreator('USeP ICT');
        $pdf->SetAuthor('ICT Department');
        $pdf->SetTitle('ICT Equipment History Sheet');
        $pdf->SetSubject('Equipment tracking');
        $pdf->SetMargins(15, 55, 15);
        $pdf->SetAutoPageBreak(true, 25);
        $pdf->AddPage();

        // Equipment details
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Ln(5);
        $pdf->Cell(35, 8, 'Equipment:', 0, 0);
        $pdf->Cell(0, 8, $equipment, 0, 1);

        $pdf->Cell(35, 8, 'Property/Serial Number:', 0, 0);
        $pdf->Cell(0, 8, $serial, 0, 1);

        $pdf->Cell(35, 8, 'Location:', 0, 0);
        $pdf->Cell(0, 8, $location, 0, 1);

        $pdf->Ln(8);

        // History table
        $header = ['Date', 'JO Number', 'Actions Taken', 'Remarks', 'Responsible SDMD Personnel'];
        $colWidths = [25, 30, 55, 45, 35];

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(220, 220, 220);

        // Header row
        foreach ($header as $i => $txt) {
            $pdf->Cell($colWidths[$i], 8, $txt, 1, 0, 'C', true);
        }
        $pdf->Ln();

        // Data rows
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetFillColor(255, 255, 255);

        foreach ($historyData as $row) {
            $pdf->Cell($colWidths[0], 7, $row['date'] ?? '', 1);
            $pdf->Cell($colWidths[1], 7, $row['jo_number'] ?? '', 1);
            $pdf->MultiCell($colWidths[2], 7, $row['action_taken'] ?? '', 1, 'L', false, 0);
            $pdf->MultiCell($colWidths[3], 7, $row['remarks'] ?? '', 1, 'L', false, 0);
            $pdf->Cell($colWidths[4], 7, $row['responsible_person'] ?? '', 1);
            $pdf->Ln();
        }

        // Add empty rows if needed
        $emptyRows = 30 - count($historyData);
        if ($emptyRows > 0) {
            for ($i = 0; $i < $emptyRows; $i++) {
                foreach ($colWidths as $w) {
                    $pdf->Cell($w, 7, '', 1);
                }
                $pdf->Ln();
            }
        }

        return $pdf;
    }
}
