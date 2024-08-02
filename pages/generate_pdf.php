<?php
require('../fpdf/fpdf.php');

class PDF extends FPDF {
    // Cabecera de página
    function Header() {
        // Logo
        $this->Image('../images/logo.jpg',10,8,33); // Asegúrate de que la ruta y extensión sean correctas
        // Arial bold 15
        $this->SetFont('Arial','B',15);
        // Movernos a la derecha
        $this->Cell(80);
        // Título
        $this->Cell(30,10,'Reporte de Inventario',0,1,'C');
        // Salto de línea
        $this->Ln(20);
    }

    // Pie de página
    function Footer() {
        // Posición a 1,5 cm del final
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial','I',8);
        // Número de página
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }

    // Tabla simple
    function BasicTable($header, $data) {
        // Cabecera
        foreach($header as $col) {
            $this->Cell(40,7,$col,1);
        }
        $this->Ln();
        // Datos
        foreach($data as $row) {
            foreach($row as $col) {
                $this->Cell(40,6,$col,1);
            }
            $this->Ln();
        }
    }
}
