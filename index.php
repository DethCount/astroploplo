<?php

ini_set('memory_limit', '4096M');

require_once(__DIR__ . '/detection/index.php');

// test_xycluster_conv_img(32, 32); exit;

// $img_name = 'DSC_0081'; $img_ext = 'jpg';
// $img_name = 'DSC_0188'; $img_ext = 'jpg';
// $img_name = 'DSC_0191'; $img_ext = 'jpg';
// $img_name = 'test_detection'; $img_ext = 'jpg';
$img_name = 'test_star_regions'; $img_ext = 'png';
// $img_name = 'perfect_star'; $img_ext = 'jpg';

$filepath = __DIR__ . '/../'.$img_name.'.'.$img_ext;
print sprintf('Image analysée : %s', $filepath) . PHP_EOL;

$img = ($img_ext == 'png') ? imagecreatefrompng($filepath) : imagecreatefromjpeg($filepath);

$height = imagesy($img);
$width = imagesx($img);

print sprintf("%s : %dx%d\n", date('H:i:s'), $width, $height);


//$img2 = detect_stars($img, $width, $height);

$star_detection_filename = __DIR__.'/var/'.$img_name.'_star_detection.PNG';
if (false || file_exists($star_detection_filename)) {
    print 'loading pre existing star detection image...' . PHP_EOL;
    $img2 = imagecreatefrompng($star_detection_filename);
} else {
    print 'Detecting stars...' . PHP_EOL;
    $img2 = detect_stars_by_luminance($img, $width, $height);
    imagepng($img2, $star_detection_filename);
}

detect_regions_by_empty_lines($img2, $width, $height);
exit;


print 'Detecting star regions...' . PHP_EOL;
$regions_stack = cluster_filter($img2);
// var_dump($regions_stack);

print 'Extracting star regions data...' . PHP_EOL;

$regions = [];
for ($i = 0; $i < count($regions_stack); $i++) {
    print 'i = ' . $i . PHP_EOL;
    $regions[$i] = [];
    $img_regions = cluster_draw($img, $regions_stack, $i, $regions[$i]);
    imagepng($img_regions, __DIR__.'/var/'.$img_name.'_star_regions_'.$i.'.PNG');
}

/*
print 'Detecting star regions...' . PHP_EOL;
$regions = detect_regions(
    $img,
    $img2,
    rgb2color(
        STAR_DETECTION_COLOR_STAR_R,
        STAR_DETECTION_COLOR_STAR_G,
        STAR_DETECTION_COLOR_STAR_B
    )
);

print 'Drawing star regions...' . PHP_EOL;
$img_reg = img_regions($img, $regions);
imagepng($img_reg, __DIR__.'/var/'.$img_name.'_star_detection_regions.PNG');

//*/

print_r($regions) . PHP_EOL;

/*
$img3 = detect_stars_luminance_grad($img, $width, $height);
imagepng($img3, __DIR__.'/var/'.$img_name.'_star_detection_grad_lumninance.PNG');
*/

/*
plot_color(function($r, $g, $b){
    return $r > 128 && $g > 128 && $b > 128;
});
*/

print sprintf("%s : end\n", date('H:i:s'));
