<?php ob_start();
// error_reporting(E_ALL);
class MpdfComponent extends Object
{
	function createPdf($testData, $forEstimate = 0)
	{
		// error_reporting(E_ALL);
		vendor('mpdf/mpdf');

		$mpdf = new mPDF();
		// $mpdf = new mPDF('utf-8', array(190,150),18);

	//call watermark content aand image
	$mpdf->SetWatermarkText('');
	$mpdf->showWatermarkText = true;
	$mpdf->watermarkTextAlpha = 0.1;
	$mpdf->useFixedNormalLineHeight = false;
	$mpdf->useFixedTextBaseline = false;
	$mpdf->fontsizes = 18;
	ob_clean();
	$mpdf->WriteHTML($testData);
	$name = rand().time();
	if( $forEstimate == 1) {
		$orgName = 'estimate_'.$name.'.pdf';
		//save the file put which location you need folder/filname
		$res = $mpdf->Output(ROOT.'/app/webroot/tech-invoice/estimate_'.$name.'.pdf', 'F');	
	} else {
		$orgName = 'tech_invoice_'.$name.'.pdf';
		//save the file put which location you need folder/filname
		$res = $mpdf->Output(ROOT.'/app/webroot/tech-invoice/tech_invoice_'.$name.'.pdf', 'F');	
	}
	return $orgName;

	//out put in browser below output function
	// $mpdf->Output();

	}
}