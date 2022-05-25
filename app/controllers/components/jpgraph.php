<?php

class JpgraphComponent extends Object
{
//    var $controller = true;
 
    function startup(&$controller)
    {

    }

    function pieGraph($x, $y, $title, $data, $legend, $theme)
    {
	vendor('jpgraph/src/jpgraph');
    vendor('jpgraph/src/jpgraph_pie');
//        vendor('jpgraph'.DS.'src'.DS.'jpgraph_pie3d');

    $graph =& new PieGraph($x, $y, "auto");

	$graph->SetFrame(false);
//	$graph->SetAntiAliasing();

	$graph->title->Set($title);
	$graph->title->SetFont(FF_VERDANA,FS_BOLD,11);
	$graph->legend->Pos(0.01,0.2);
//	$graph->legend->SetFillColor('white');

	$p1 = new PiePlot($data);
	$p1->SetTheme($theme);
	$p1->SetCenter(0.3);
//	$p1->SetAngle(30);
	$p1->value->SetFont(FF_VERDANA,FS_NORMAL,10);
	$p1->value->SetColor("black");
	$p1->value->HideZero();
	$p1->SetLegends($legend);

	$graph->Add($p1);

	$graph->Stroke();

	return $graph;
        //$controller->set(’jpgrapho’, $graph);
    }

    function barGraph($x, $y, $title, $datay, $datax, $xtitle, $ytitle)
    {
	vendor('jpgraph/src/jpgraph');
        vendor('jpgraph/src/jpgraph_bar');

        $graph =& new Graph($x, $y, "auto");

	$graph->SetFrame(false);

	$graph->title->Set($title);
	$graph->title->SetFont(FF_VERDANA,FS_BOLD,11);

//	$graph->xaxis->title->Set($xtitle);
//	$graph->yaxis->title->Set($ytitle);
//	$graph->yaxis->title->SetFont(FF_VERDANA,FS_BOLD);
//	$graph->xaxis->title->SetFont(FF_VERDANA,FS_BOLD);

	$graph->SetScale("textlin");

	$graph->img->SetMargin(50,20,40,20);
	$graph->yaxis->SetTitleMargin(65);
	$graph->yaxis->scale->SetGrace(30);

	// Turn the tickmarks
	$graph->xaxis->SetTickSide(SIDE_DOWN);
	$graph->yaxis->SetTickSide(SIDE_LEFT);
	$graph->xaxis->SetTickLabels($datax);

	$graph->yaxis->SetLabelFormat('$ %d');

	$bplot = new BarPlot($datay);
	$bplot->SetFillColor("orange");

	$bplot->value->SetFormat(" $ %2.1f",70);
	$bplot->value->SetFont(FF_VERDANA,FS_BOLD,9);
	$bplot->value->SetColor("black");
	$bplot->value->SetAngle(45);
	$bplot->value->Show();


	$graph->Add($bplot);

	$graph->Stroke();

	return $graph;
    }
}
?>
