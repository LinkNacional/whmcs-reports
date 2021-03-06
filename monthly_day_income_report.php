<?php

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

$reportdata['title'] = "Day Monthy Income Report for " . $currentyear;
$reportdata['description'] = "This report shows the income received broken down by day on month converted to the base currency using rates at the time of the transaction";
$reportdata['yearspagination'] = false;

$currency = getCurrency(null, 1);

$reportdata['tableheadings'] = array(
    "Day",
    "Amount In",
    "Fees",
    "Amount Out",
    "Balance"
);

$reportvalues = array();
$results = Capsule::table('tblaccounts')
    ->select(
        Capsule::raw("date_format(date,'%d') as day"),
        Capsule::raw("date_format(date,'%m') as month"),
        Capsule::raw("date_format(date,'%Y') as year"),
        Capsule::raw("SUM(amountin/rate) as amountin"),
        Capsule::raw("SUM(fees/rate) fees"),
        Capsule::raw("SUM(amountout/rate) as amountout")
    )
    ->where('date', '>=', ($currentyear - 2 ) . '-01-01')
    ->groupBy(Capsule::raw("date_format(date,'%D %M %Y')"))
    ->orderBy('date', 'asc')
    ->get()
    ->all();
    //print_r($results);
foreach ($results as $result) {
    $day = (int) $result->day;
    $month = (int) $result->month;
    $year = (int) $result->year;
    $amountin = $result->amountin;
    $fees = $result->fees;
    $amountout = $result->amountout;
    $monthlybalance = $amountin - $fees - $amountout;

    $reportvalues[$year][$day] = [
        $amountin,
        $fees,
        $amountout,
        $monthlybalance,
    ];
}

for ($i = 1; $i <= date('t'); $i++) {

    $days[$i] = $i; 
//for ($days as $d => $monthName) {

    //if ($dayName) {

        $amountin = $reportvalues[$currentyear][$i][0];
        $fees = $reportvalues[$currentyear][$i][1];
        $amountout = $reportvalues[$currentyear][$i][2];
        $monthlybalance = $reportvalues[$currentyear][$i][3];
        
        if($i <= 9){
            $dayForma = "0$i" ;
        }else{
            $dayForma = $i;
        }

        $reportdata['tablevalues'][] = array(
            date('D', strtotime($currentyear.date('m').$dayForma)) . ' ' .$dayForma,
            formatCurrency($amountin),
            formatCurrency($fees),
            formatCurrency($amountout),
            formatCurrency($monthlybalance),
        );

        $overallbalance += $monthlybalance;

  //  }

}

$reportdata['footertext'] = '<p align="center"><strong>Balance: ' . formatCurrency($overallbalance) . '</strong></p>';

$chartdata['cols'][] = array('label'=>'Days Range','type'=>'string');
$chartdata['cols'][] = array('label'=>$currentyear-2,'type'=>'number');
$chartdata['cols'][] = array('label'=>$currentyear-1,'type'=>'number');
$chartdata['cols'][] = array('label'=>$currentyear,'type'=>'number');

for ($i = 1; $i <= date('t'); $i++) {
    $chartdata['rows'][] = array(
        'c'=>array(
            array(
                'v'=>$i,
            ),
            array(
                'v'=>$reportvalues[$currentyear-2][$i][3],
                'f'=>formatCurrency($reportvalues[$currentyear-2][$i][3])->toFull(),
            ),
            array(
                'v'=>$reportvalues[$currentyear-1][$i][3],
                'f'=>formatCurrency($reportvalues[$currentyear-1][$i][3])->toFull(),
            ),
            array(
                'v'=>$reportvalues[$currentyear][$i][3],
                'f'=>formatCurrency($reportvalues[$currentyear][$i][3])->toFull(),
            ),
        ),
    );
}

$args = array();
$args['colors'] = '#3070CF,#F9D88C,#cb4c30';
$args['chartarea'] = '80,20,90%,350';

$reportdata['headertext'] = $chart->drawChart('Column',$chartdata,$args,'400px');
