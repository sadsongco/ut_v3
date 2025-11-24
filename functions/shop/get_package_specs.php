<?php

function getPackageSpecs($cart_contents)
{
    $all_e_delivery = true;
    $length = $width = $depth = 0;
    $items_weight = 0;
    $packaging_classifications = [];
    foreach ($cart_contents['items'] as $item) {
        if ($item['e_delivery'] == 1) continue;
        addPackagingClassification($item, $packaging_classifications);
        $all_e_delivery = false;
        $items_weight += getItemWeight($item);
        if ($item['length_mm'] > $length) $length = $item['length_mm'];
        if ($item['width_mm'] > $width) $width = $item['width_mm'];
        $depth += $item['depth_mm'] * $item['quantity'];
    }
    if (isset($cart_contents['bundles']) && sizeof($cart_contents['bundles']) > 0) {
        foreach ($cart_contents['bundles'] as $bundle) {
            foreach ($bundle['items'] as $item) {
                if ($item['e_delivery'] == 1) continue;
                addPackagingClassification($item, $packaging_classifications);
                $all_e_delivery = false;
                $items_weight += getItemWeight($item);
                if ($item['length_mm'] > $length) $length = $item['length_mm'];
                if ($item['width_mm'] > $width) $width = $item['width_mm'];
                $depth += $item['depth_mm'] * $bundle['quantity'];
            }
        }
    }
    ksort($packaging_classifications);
    $arr = [];
    foreach ($packaging_classifications as $key=>$value) {
        $arr[] = $key . "_" . $value;
    }
    $packaging_classification = implode("-", $arr);
    if (!isset(ITEM_PACKAGES[$packaging_classification])) {
        $packaging_classification = "DEFAULT";
    }
    $package_specs = ITEM_PACKAGES[$packaging_classification];
    $total_weight = $items_weight + $package_specs["weight_g"];

    if ($all_e_delivery) {
        return [
            "e_delivery"=>true
        ];
    }

    return [
        "weight"=>$total_weight,
        "length"=>$package_specs["length_mm"],
        "width"=>$package_specs["width_mm"],
        "depth"=>$package_specs["depth_mm"],
        "package_format"=>$package_specs["name"],
        "package_price"=>$package_specs["unit_price_p"] / 100
    ];
}

function addPackagingClassification ($item, &$packaging_classifications)
{
    if (!isset($packaging_classifications[$item['packaging_classification']])) {
        $packaging_classifications[$item['packaging_classification']] = $item['quantity'];
    } else {
        $packaging_classifications[$item['packaging_classification']] += $item['quantity'];
    }
}

function getItemWeight ($item) {
    if (isset($item['option_id']) && $item['option_id']) {
        return $item['option_weight'] * $item['quantity']; //stored in grams. annoyingly
    }
    return $item['weight'] * $item['quantity'] * 1000;
}