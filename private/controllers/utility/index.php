<?php

$vat_periods = [
    "P1" => [
        "start" => "12-01",
        "end" => "02-28"
    ],
    "P2" => [
        "start" => "03-01",
        "end" => "05-31"
    ],
    "P3" => [
        "start" => "06-01",
        "end" => "08-31"
    ],
    "P4" => [
        "start" => "09-01",
        "end" => "11-30"
    ]
];

$tax_year = [
    "start" => "04-05",
    "end" => "04-04"
];

$start_year = 2024;
$vat_start_year = 2025;

$today = new DateTime();
$this_date = $today->format("Y-m-d");

$this_year = (int)$today->format("Y");
$period_options = [];
while ($this_year >= $vat_start_year) {
    // find latest VAT period, then work backwards
    $vat_backwards = array_reverse($vat_periods);
    foreach ($vat_backwards as $period => $dates) {
        $period_start_year = $this_year;
        if ($period == 'P1') {
            $period_start_year = $this_year - 1;
            if (isLeapYear($this_year)) {
                $dates['end'] = "02-29";
            }
        }
        if ($period_start_year < $start_year) break;
        if ($this_date < $this_year . "-" . $dates['end']) continue;
        if ($this_date >= $dates['start']) {
            $period_options[] = [
                "name" => $this_year . " " . $period,
                "start" => $period_start_year . "-" . $dates['start'],
                "end" => $this_year . "-" . $dates['end']];
        }
    }
    $this_year--;
}

$this_year = (int)$today->format("Y");
while ($this_year >= $start_year) {
    if ($this_date >= $tax_year['start'] && $this_year > $start_year) {
        $period_options[] = [
            "name" => $this_year-1 . " - " . $this_year,
            "start" => $this_year-1 . "-" . $tax_year['start'],
            "end" => $this_year . "-" . $tax_year['end']
        ];
    }
    $this_year--;
}

echo $this->renderer->render('utility/index', ["period_options" => $period_options]);

function isLeapYear($year) {
    if (($year % 4 == 0 && $year % 100 != 0) 
        || ($year % 400 == 0)) {
        return true;
    } else {
        return false;
    }
}