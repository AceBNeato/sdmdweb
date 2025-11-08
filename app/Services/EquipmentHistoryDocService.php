<?php

namespace App\Services;

use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class EquipmentHistoryDocService
{
    public function fillTemplate(array $data, string $outputPath): void
    {
        // Try to use template if it exists
        $templatePath = public_path('Equipment History Sheet.docx');

        if (file_exists($templatePath) && extension_loaded('zip')) {
            // Use template method if ZipArchive is available
            $this->fillTemplateWithZip($data, $outputPath);
        } else {
            // Fallback: Create document from scratch
            $this->createDocumentFromScratch($data, $outputPath);
        }
    }

    private function fillTemplateWithZip(array $data, string $outputPath): void
    {
        $template = new TemplateProcessor(public_path('Equipment History Sheet.docx'));

        // Basic equipment information - simple placeholders
        $template->setValue('${equipment}', $data['equipment'] ?? '');
        $template->setValue('${serial_number}', $data['serial_number'] ?? '');
        $template->setValue('${location}', $data['location'] ?? '');

        // Handle history table - clone a row with placeholders and fill each row
        $history = $data['history'] ?? [];
        if (!empty($history)) {
            $template->cloneRow('${history_date}', count($history));
            foreach ($history as $index => $entry) {
                $row = $index + 1;
                $template->setValue("${history_date}#{$row}", $entry['date'] ?? '');
                $template->setValue("${history_jo}#{$row}", $entry['jo_number'] ?? '');
                $template->setValue("${history_actions}#{$row}", $entry['action_taken'] ?? '');
                $template->setValue("${history_remarks}#{$row}", $entry['remarks'] ?? '');
                $template->setValue("${history_person}#{$row}", $entry['responsible_person'] ?? '');
            }
        }

        $template->saveAs($outputPath);
    }

    private function createDocumentFromScratch(array $data, string $outputPath): void
    {
        $phpWord = new PhpWord();

        // Set document properties
        $properties = $phpWord->getDocInfo();
        $properties->setCreator('SDMD System');
        $properties->setTitle('ICT Equipment History Sheet');

        // Create section
        $section = $phpWord->addSection([
            'marginLeft' => 600,
            'marginRight' => 600,
            'marginTop' => 600,
            'marginBottom' => 600,
        ]);

        // Header
        $header = $section->addHeader();
        $header->addText('Republic of the Philippines', ['bold' => true], ['alignment' => 'center']);
        $header->addText('University of Southeastern Philippines', ['bold' => true, 'size' => 14], ['alignment' => 'center']);
        $header->addText('Iñigo St., Bo. Obrero, Davao City 8000', [], ['alignment' => 'center']);
        $header->addText('Systems and Data Management Division (SDMD)', ['bold' => true], ['alignment' => 'center']);

        // Title
        $section->addTextBreak(1);
        $section->addText('ICT EQUIPMENT HISTORY SHEET', ['bold' => true, 'size' => 16], ['alignment' => 'center']);
        $section->addTextBreak(1);

        // Equipment Info Table
        $table = $section->addTable();
        $table->addRow();
        $table->addCell(3000)->addText('Equipment:', ['bold' => true]);
        $table->addCell(6000)->addText($data['equipment'] ?? '');

        $table->addRow();
        $table->addCell(3000)->addText('Property/Serial Number:', ['bold' => true]);
        $table->addCell(6000)->addText($data['serial_number'] ?? '');

        $table->addRow();
        $table->addCell(3000)->addText('Location:', ['bold' => true]);
        $table->addCell(6000)->addText($data['location'] ?? '');

        $section->addTextBreak(1);

        // History Table
        $historyTable = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80
        ]);

        // Header row
        $historyTable->addRow();
        $historyTable->addCell(1000)->addText('Date', ['bold' => true], ['alignment' => 'center']);
        $historyTable->addCell(1500)->addText('JO Number', ['bold' => true], ['alignment' => 'center']);
        $historyTable->addCell(3000)->addText('Actions Taken', ['bold' => true], ['alignment' => 'center']);
        $historyTable->addCell(2500)->addText('Remarks', ['bold' => true], ['alignment' => 'center']);
        $historyTable->addCell(2000)->addText('Responsible SDMD Personnel', ['bold' => true], ['alignment' => 'center']);

        // Data rows
        $history = $data['history'] ?? [];
        if (!empty($history)) {
            foreach ($history as $entry) {
                $historyTable->addRow();
                $historyTable->addCell(1000)->addText($entry['date'] ?? '');
                $historyTable->addCell(1500)->addText($entry['jo_number'] ?? '');
                $historyTable->addCell(3000)->addText($entry['action_taken'] ?? '');
                $historyTable->addCell(2500)->addText($entry['remarks'] ?? '');
                $historyTable->addCell(2000)->addText($entry['responsible_person'] ?? '');
            }
        }

        // Add empty rows to make 30 total
        $totalRows = count($history);
        for ($i = $totalRows; $i < 30; $i++) {
            $historyTable->addRow();
            $historyTable->addCell(1000)->addText('');
            $historyTable->addCell(1500)->addText('');
            $historyTable->addCell(3000)->addText('');
            $historyTable->addCell(2500)->addText('');
            $historyTable->addCell(2000)->addText('');
        }

        // Footer
        $section->addTextBreak(1);
        $section->addText('Page 1 of 1', [], ['alignment' => 'right']);

        // Save the document
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($outputPath);
    }
}
