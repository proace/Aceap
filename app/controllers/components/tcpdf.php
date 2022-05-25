<?php ob_start();

class TcpdfComponent extends Object
{
	function createPdf($testData)
	{

		vendor('TCPDF/tcpdf');

		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, false, 'ISO-8859-1', false);
 
		// $pdf->SetCreator("Pro Ace");
		// $pdf->SetAuthor('Pro Ace');
		// $pdf->SetTitle('Pro Ace');
		 
		$pdf->setHeaderData('hvac_logo.png', PDF_HEADER_LOGO_WIDTH, '', '', array(0, 6, 255), array(0, 64, 128));
		// $pdf->setFooterData(array(0,64,0), array(0,64,128));
		 
		// $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		// $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
		 
		// set default monospaced font
		// $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		 
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		 
		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		 
		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		 
		// ---------------------------------------------------------
		 
		// set default font subsetting mode
		 
		// $pdf->setFont('helvetica', '', 12, '', true);
		 // define('K_PATH_MAIN', '/acesys/app/webroot/img/');

		$pdf->AddPage();
		
		// $path = K_PATH_MAIN.'tcpdf_logo.jpg';
		// $template = $testData;
		// $template = str_replace('{img_url}', $path ,$template );
		
		/*$template = '<img src="<?=$path?>" style="max-width: 100%;" alt="" >';*/
		$pdf->writeHTML($testData, true, false, false, false, '');
		// $pdf->Write(5, $testData, '', 0, '', false, 0, false, false, 0);

		 $name = time();
		 $orgname = 'ace_contract_'.$name.'.pdf';
		 ob_end_clean();
		 /*local*/
		// $pdf->Output(ROOT.'\tech_contract\ace_contract_'.$name.'.pdf', 'F');
		
		/*server*/
		// $pdf->Output(ROOT.'/tech_contract/ace_contract_'.$name.'.pdf', 'F');
		$pdf->Output(ROOT.'/app/webroot/contract/ace_contract_'.$name.'.pdf', 'F');
		
		return $orgname;
		// $pdf->Output();
	}
}