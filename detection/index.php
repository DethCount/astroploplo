<?php
define('MAX_COLOR_CHANEL_SQUARED', 255*255);
define('STAR_DETECTION_MIN_DELTA_D2_FROM_WHITE', 1);
define('STAR_DETECTION_MAX_D2_FROM_WHITE', 3 * MAX_COLOR_CHANEL_SQUARED);
define('STAR_DETECTION_LUMINANCE_R_FACTOR', 0.2126);
define('STAR_DETECTION_LUMINANCE_G_FACTOR', 0.7152);
define('STAR_DETECTION_LUMINANCE_B_FACTOR', 0.0722);
define('STAR_DETECTION_MAX_LUMINANCE', sqrt(
    STAR_DETECTION_LUMINANCE_R_FACTOR * MAX_COLOR_CHANEL_SQUARED
    + STAR_DETECTION_LUMINANCE_G_FACTOR * MAX_COLOR_CHANEL_SQUARED
    + STAR_DETECTION_LUMINANCE_B_FACTOR * MAX_COLOR_CHANEL_SQUARED
));
define('STAR_DETECTION_MIN_LUMINANCE_RATIO', 0.22);
define('STAR_DETECTION_LUMINANCE_DYNAMIC_MIN', 0.01);
define('STAR_DETECTION_LUMINANCE_STATIC_MAX', 0.1*STAR_DETECTION_LUMINANCE_DYNAMIC_MIN);
define('STAR_DETECTION_COLOR_STAR_R', 255);
define('STAR_DETECTION_COLOR_STAR_G', 0);
define('STAR_DETECTION_COLOR_STAR_B', 0);
define('STAR_DETECTION_COLOR_BLACKHOLE_R', 0);
define('STAR_DETECTION_COLOR_BLACKHOLE_G', 0);
define('STAR_DETECTION_COLOR_BLACKHOLE_B', 255);
define('STAR_DETECTION_COLOR_CONTINUE_R', 0);
define('STAR_DETECTION_COLOR_CONTINUE_G', 255);
define('STAR_DETECTION_COLOR_CONTINUE_B', 0);

function distance_squared_from_color($color, $refR = 255, $refG = 255, $refB = 255)
{
    $dr = $refR - ($color >> 16 & 0xFF);
    $dg = $refG - ($color >> 8 & 0xFF);
    $db = $refB - ($color & 0xFF);

    // print sprintf('%d:%d:%d => %d', $dr, $dg, $db, $dr * $dr + $dg * $dg + $db * $db) . PHP_EOL;

    return $dr * $dr + $dg * $dg + $db * $db;
}

function color2rgb($color, & $r = 0, & $g = 0, & $b = 0)
{
    $r = $color >> 16 & 0xFF;
    $g = $color >> 8 & 0xFF;
    $b = $color & 0xFF;
}

function rgb2color($r, $g, $b, & $color = 0)
{
    return $r * 256 * 256 + $g * 256 + $b;
}

function luminance($color)
{
    color2rgb($color, $r, $g, $b);
    return sqrt(
        STAR_DETECTION_LUMINANCE_R_FACTOR*$r*$r
        + STAR_DETECTION_LUMINANCE_G_FACTOR*$g*$g
        + STAR_DETECTION_LUMINANCE_B_FACTOR*$b*$b
    );
}

function detect_stars($img, $width, $height)
{
    $img2 = imagecreatetruecolor($width, $height);
    $colors  = [
        'star' => imagecolorallocate($img2, 255, 0, 0),
        'blackhole' => imagecolorallocate($img2, 0, 0, 255),
        'continue' => imagecolorallocate($img2, 0, 255, 0),
    ];

    $MAX_CONTINUE = STAR_DETECTION_MIN_DELTA_D2_FROM_WHITE/1000;
    $TMP_MAX_DS = 8 * STAR_DETECTION_MAX_D2_FROM_WHITE;
    $DS_PRECISION = 1000;
    $maxDs = null;
    $minDs = null;

    for ($y = 1 ; $y < $height - 1 ; $y++) {
        for ($x = 1 ; $x < $width - 1 ; $x++) {
            // INT max : STAR_DETECTION_MAX_D2_FROM_WHITE, min : 0
            $color = imagecolorat($img, $x, $y);
            $r = $color >> 16 & 0xFF;
            $g = $color >> 8 & 0xFF;
            $b = $color & 0xFF;
            $dw = distance_squared_from_color($color);

            // INT max : TMP_MAX_DS, min : -TMP_MAX_DS
            $ds = sqrt(distance_squared_from_color(imagecolorat($img, $x - 1, $y    ), $r, $g, $b))
                + sqrt(distance_squared_from_color(imagecolorat($img, $x + 1, $y    ), $r, $g, $b))
                + sqrt(distance_squared_from_color(imagecolorat($img, $x    , $y - 1), $r, $g, $b))
                + sqrt(distance_squared_from_color(imagecolorat($img, $x    , $y + 1), $r, $g, $b))
                + sqrt(distance_squared_from_color(imagecolorat($img, $x + 1, $y + 1), $r, $g, $b))
                + sqrt(distance_squared_from_color(imagecolorat($img, $x - 1, $y - 1), $r, $g, $b))
                + sqrt(distance_squared_from_color(imagecolorat($img, $x - 1, $y + 1), $r, $g, $b))
                + sqrt(distance_squared_from_color(imagecolorat($img, $x + 1, $y - 1), $r, $g, $b))
                ;


            $ds = $ds != 0 ? $ds / (8 * sqrt(3 * 255 * 255)) : 0; // distance btw black and white;
            $dsc = $ds;
            // $ds -= 1;
            //$ds = $ds >= $mid ? ($ds == $mid ? 0 : 1) : -1;

            //$ds *= $dw / STAR_DETECTION_MAX_D2_FROM_WHITE;


            // DOUBLE max : 1, min : -1
            // $ds /= $TMP_MAX_DS;

            // if ($ds > 1 || $ds < -1) {
                print sprintf('ERR (%f,%f) = %f (%f, %f)', $x, $y, $ds, $dw, $dsc) . PHP_EOL;
            //}

            if ($minDs == null || $ds < $minDs) $minDs = $ds;
            if ($maxDs == null || $ds > $maxDs) $maxDs = $ds;

            $ads = abs($ds);

            if ($ds != 0 && $ads > 0.01) {
                imagesetpixel($img2, $x, $y, $ds > 0 ? $colors['blackhole'] : $colors['star']);
                //if ($ads > 100) print sprintf('(%d,%d) = %d (%d)', $x, $y, $ds, $dw) . PHP_EOL;
            } elseif ($ads < $MAX_CONTINUE) {
                imagesetpixel($img2, $x, $y, $colors['continue']);
            }
        }

    }

    print sprintf('DS (%f,%f)', $minDs, $maxDs) . PHP_EOL;

    return $img2;
}

function detect_stars_by_luminance($img, $width, $height) {
    $img2 = imagecreatetruecolor($width, $height);
    $colors  = [
        'star' => imagecolorallocate(
            $img2,
            STAR_DETECTION_COLOR_STAR_R,
            STAR_DETECTION_COLOR_STAR_G,
            STAR_DETECTION_COLOR_STAR_B
        ),
        'blackhole' => imagecolorallocate(
            $img2,
            STAR_DETECTION_COLOR_BLACKHOLE_R,
            STAR_DETECTION_COLOR_BLACKHOLE_G,
            STAR_DETECTION_COLOR_BLACKHOLE_B
        ),
        'continue' => imagecolorallocate(
            $img2,
            STAR_DETECTION_COLOR_CONTINUE_R,
            STAR_DETECTION_COLOR_CONTINUE_G,
            STAR_DETECTION_COLOR_CONTINUE_B
        )
    ];

    $min_surface_lumi = null;
    $max_surface_lumi = null;
    $min_luminance_ratio = null;
    $max_luminance_ratio = null;

    for ($y = 1 ; $y < $height - 1 ; $y++) {
        for ($x = 1 ; $x < $width - 1 ; $x++) {
            $lumi = luminance(imagecolorat($img, $x, $y));
            $luminance_ratio = $lumi / STAR_DETECTION_MAX_LUMINANCE;

            if ($min_luminance_ratio === null || $luminance_ratio < $min_luminance_ratio) {
                $min_luminance_ratio = $luminance_ratio;
            }
            if ($max_luminance_ratio === null || $luminance_ratio > $max_luminance_ratio) {
                $max_luminance_ratio = $luminance_ratio;
            }

            if ($luminance_ratio <= STAR_DETECTION_MIN_LUMINANCE_RATIO) {
                // print sprintf('skipped (%d,%d) %f', $x, $y, $luminance_ratio). PHP_EOL;
                continue;
            }

            $surface_lumi = 8 * $lumi
                - (
                    luminance(imagecolorat($img, $x - 1, $y    ))
                    + luminance(imagecolorat($img, $x + 1, $y    ))
                    + luminance(imagecolorat($img, $x    , $y - 1))
                    + luminance(imagecolorat($img, $x    , $y + 1))
                    + luminance(imagecolorat($img, $x + 1, $y + 1))
                    + luminance(imagecolorat($img, $x - 1, $y - 1))
                    + luminance(imagecolorat($img, $x - 1, $y + 1))
                    + luminance(imagecolorat($img, $x + 1, $y - 1))
                );

            // max : 1, min : -1
            $surface_lumi = $surface_lumi != 0 ? $surface_lumi / (8 * STAR_DETECTION_MAX_LUMINANCE) : 0;
            $slc = $surface_lumi;

            // print sprintf('(%f,%f) = %f (%f, %f)', $x, $y, $surface_lumi, $lumi, $slc) . PHP_EOL;

            if ($min_surface_lumi == null || $surface_lumi < $min_surface_lumi) $min_surface_lumi = $surface_lumi;
            if ($max_surface_lumi == null || $surface_lumi > $max_surface_lumi) $max_surface_lumi = $surface_lumi;

            $asl = abs($surface_lumi);

            if ($surface_lumi != 0 && $asl >= STAR_DETECTION_LUMINANCE_DYNAMIC_MIN) {
                imagesetpixel($img2, $x, $y, $surface_lumi > 0 ? $colors['star'] : $colors['blackhole']);
            } elseif ($asl <= STAR_DETECTION_LUMINANCE_STATIC_MAX) {
                imagesetpixel($img2, $x, $y, $colors['continue']);
            }
        }
    }

    print sprintf('PIXEL SURFACE LUMINANCE [%f,%f]', $min_surface_lumi, $max_surface_lumi) . PHP_EOL;
    print sprintf('LUMINANCE RATIO [%f,%f]', $min_luminance_ratio, $max_luminance_ratio) . PHP_EOL;

    return $img2;
}

function detect_stars_luminance_grad($img, $width, $height)
{
    $img2 = imagecreatetruecolor($width, $height);
    $colors  = [
        'star' => imagecolorallocate($img2, 255, 0, 0),
        'blackhole' => imagecolorallocate($img2, 0, 0, 255),
        'continue' => imagecolorallocate($img2, 0, 255, 0),
    ];

    $min_surface_lumi = null;
    $max_surface_lumi = null;
    $min_luminance_ratio = null;
    $max_luminance_ratio = null;

    for ($y = 1 ; $y < $height - 1 ; $y++) {
        for ($x = 1 ; $x < $width - 1 ; $x++) {
            $lumi = luminance(imagecolorat($img, $x, $y));

            $glx = $lumi - luminance(imagecolorat($img, $x - 1, $y));
            $gly = $lumi - luminance(imagecolorat($img, $x, $y - 1));

            $gl = $gly != 0 ? $glx/$gly : 0;
            if ($gl == 0) {
                print sprintf('GL %f (%f, %f)', $gl, $glx, $gly) . PHP_EOL;
                imagesetpixel($img2, $x, $y, imagecolorallocate($img2, 255, 0, 0));
            }
            continue;

            $glc = $gl;
            $gl = $gl != 0 ? round(255*255*255/$gl) : 0;
            color2rgb($gl, $glr, $glg, $glb);

            print sprintf('(%f,%f) = %f (%f, %f)(%d,%d,%d)', $x, $y, $gl, $lumi, $glc, $glr, $glg, $glb) . PHP_EOL;
            $agl = abs($gl);


            imagesetpixel($img2, $x, $y, imagecolorallocate($img2, $glr, $glg, $glb));
        }
    }

    print sprintf('PIXEL SURFACE LUMINANCE [%f,%f]', $min_surface_lumi, $max_surface_lumi) . PHP_EOL;
    print sprintf('LUMINANCE RATIO [%f,%f]', $min_luminance_ratio, $max_luminance_ratio) . PHP_EOL;

    return $img2;
}

function dec2quat($nb)
{
    $conv = '';
    do {
        $pow = 1;
        $nb4 = floor($nb / 4);
        $mod = $nb - $nb4 * 4;
        $conv = $mod . $conv;
        $nb = $nb4;
    } while ($nb4 >= 4);

    $conv = $nb4 . $conv;

    return $conv == '00' ? '0' : $conv;
}

function bin_pad($nb, $l)
{
    $nbb = is_string($nb) ? $nb : decbin($nb);
    $cl = strlen($nbb);
    while ($cl < $l) {
        $nbb = '0' . $nbb;
        $cl++;
    }

    return $nbb;
}

function cluster2xy($coord, & $x = 0, & $y = 0)
{
    $x = 0;
    $y = 0;
    $lm = strlen($coord);

    $f = pow(2, 0.5 * $lm - 1);
    for ($l = 0; $l < $lm; $l++) {
        if ($l % 2 == 0) {
            $x += $f * intval($coord[$l]);
        } else {
            $y += $f * intval($coord[$l]);
            $f *= 0.5;
        }
    }

    return [$x, $y];
}

function test_xycluster_conv_img($width = 32, $height = 32)
{
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $height; $x++) {
            if(!test_xycluster_conv($x, $y, $width, $height)) {
                break 2;
            }
        }
    }
}

function test_xycluster_conv($x, $y, $width, $height, $it = null, $debug = false)
{
    $coord = xy2cluster_bw($x, $y, $width, $height, $it, $debug);
    $res = cluster2xy($coord);

    if ($res[0] != $x || $res[1] != $y) {
        var_dump($x);
        var_dump($y);
        var_dump($coord);
        var_dump($res);

        return false;
    }

    return true;
}

function cluster_filter($img, $it = null, $debug = false)
{
    $width = imagesx($img);
    $height = imagesy($img);

    $start = time();

    $L = ceil(log(max($width, $height), 2));

    $regions_stack = [];
    for ($y = 0 ; $y < $height ; $y++) {
        print (time() - $start) . 's : Finding regions... '
            . number_format(100*($y/$height), 2)
            . ' ' . count($regions_stack) . PHP_EOL;

        for ($x = 0 ; $x < $width ; $x++) {
            $color = imagecolorat($img, $x, $y);
            if ($color === 0) {
                continue;
            }

            $coord = xy2cluster_bw($x, $y, $width, $height, $it, $debug);
            $pixel = [$x, $y];

            for ($l = 0; $l < $L; $l++) {
                $key = '#'.substr($coord, 0, 2 * ($l + 1));
                if (!isset($regions_stack[$l])) {
                    $regions_stack[$l] = [];
                }

                if (!isset($regions_stack[$l][$key])) {
                    $regions_stack[$l][$key] = $pixel;
                }
            }
        }
    }

    return $regions_stack;
}

function cluster_draw($img, array & $regions_stack, $i, array & $regions = array())
{
    $width = imagesx($img);
    $height = imagesy($img);
    $w = pow(2, $i + 1);
    $img2 = imagecreate($w, $w);
    $black = imagecolorallocate($img2, 0, 0, 0);
    $red = imagecolorallocate($img2, 255, 0, 0);
    imagefilledrectangle($img2, 0, 0, $w, $w, $black);

    $curr = reset($regions_stack[$i]);
    do {
        $key = key($regions_stack[$i]);
        $k = substr($key, 1);
        $base_k = substr($k, 2);
        $bx = intval(substr($k, 0, 1));
        $by = intval(substr($k, 1, 1));
        cluster2xy($k, $rx, $ry); // coordinates at current resolution

        imagesetpixel($img2, $x, $y, $red);

        region_create($regions, [$x, $y], $k);

        $next = (!$bx) . $by . $base_k;
        if (isset($regions_stack[$i]['#' . $next])) {
            $p = cluster2xy($next);
            region_update($regions, $k, $p);
            imagesetpixel($img2, $p[0], $p[1], $red);
            unset($regions_stack[$i]['#' . $next]);
        }

        $next = $bx . (!$by) . $base_k;
        if (isset($regions_stack[$i]['#' . $next])) {
            $p = cluster2xy($next);
            region_update($regions, $k, $p);
            imagesetpixel($img2, $p[0], $p[1], $red);
            unset($regions_stack[$i]['#' . $next]);
        }

        $next = (!$bx) . (!$by) . $base_k;
        if (isset($regions_stack[$i]['#' . $next])) {
            $p = cluster2xy($next);
            region_update($regions, $k, $p);
            imagesetpixel($img2, $p[0], $p[1], $red);
            unset($regions_stack[$i]['#' . $next]);
        }
    } while ($curr = next($regions_stack[$i]));

    return $img2;
}

/*
function xy2cluster ($x, $y, $max_x, $max_y, $it = null, $debug = false)
{
    $imax = ceil(log(max($max_x, $max_y), 2));
    if ($debug) print sprintf('imax = %d', $imax) . PHP_EOL;

    $ii = $it === null ? $imax : min($it, $imax);

    $coord = 0;
    for ($i = 0; $i <= $ii; $i++) {
        $l = pow(2, $imax);
        $g =  1 / ($i + 1);
        $fx = $g * pow(2, 2 * $i);
        $fy = $g * pow(2, 2 * $i + 1);
        if ($debug) print sprintf('i = %d, l = %f, g = %f, fx = %f, fy = %f', $i, $l, $g, $fx, $fy) . PHP_EOL;

        $xsum = 0;
        $ysum = 0;
        for ($j = 0; $j <= $i ; $j++) {
            $h = pow(2, $j + 1) / $l;
            if ($debug) print sprintf('j = %d, h = %f', $j, $h) . PHP_EOL;
            $xsum += floor($x * $h);
            $ysum += floor($y * $h);
        }

        if ($debug) print sprintf('xsum = %f, ysum = %f', $xsum, $ysum) . PHP_EOL;

        $coord += $fx * $xsum + $fy * $ysum;
    }

    return $coord;
}
*/


function xy2cluster($x, $y, $width, $height, $it = null, $debug = false)
{
    $L = ceil(log(max($width, $height), 2));
    $ii = $it === null ? $L : min($it, $L - 1);

    if ($debug) print sprintf('L = %d, ii = %d', $L, $ii) . PHP_EOL;

    $coord = 0;
    for ($i = 0; $i <= $ii; $i++) {
        $l = pow(2, $i - $L + 1);
        $fx = pow(2, 2 * $i);
        $fy = pow(2, 2 * $i + 1);
        $xsum = 1 - round(0.5 * sin(M_PI * $x * $l));
        $ysum = 1 - round(0.5 * sin(M_PI * $y * $l));

        if ($debug) print sprintf('i = %d, l = %f, g = %f, fx = %f, fy = %f', $i, $l, $g, $fx, $fy) . PHP_EOL;
        if ($debug) print sprintf('xsum = %f, ysum = %f', $xsum, $ysum) . PHP_EOL;

        $coord += $fx * $xsum + $fy * $ysum;
    }

    return $coord;
}

function xy2cluster_bw($x, $y, $width, $height, $it = null, $debug = false)
{
    $coord = 0b0;
    $xbin = decbin((int) $x);
    $ybin = decbin((int) $y);

    $lx = strlen($xbin);
    $ly = strlen($ybin);

    $lm = $lx;
    if ($lx > $ly) {
        $ybin = bin_pad($ybin, $lx);
    } elseif($lx != $ly) {
        $xbin = bin_pad($xbin, $ly);
        $lm = $ly;
    }

    $L = ceil(log(max($width, $height), 2));
    //$ii = $it === null ? $L - 1 : min($it, $L - 1);

    if ($debug) print sprintf('L = %d, ii = %d, lx = %d, ly = %d', $L, $ii, $lx, $ly) . PHP_EOL;

    for ($i = 0; $i < $lm; $i++) {

        if ($i > 0) $coord = $coord << 1;

        $coord |= $xbin[$i];
        $coord = $coord << 1;
        $coord |= $ybin[$i];
    }

    $res = bin_pad($coord, $L + $L);

    if ($debug) print sprintf('(%d, %d) -> %s (%d)', $x, $y, $res, $coord) . PHP_EOL;

    return $res;
}

function region_create(array & $regions, array $pixel, $ridx = null)
{
    $regions[null === $ridx ? 0 : $ridx] = [
        'nb_pixels' => 1,
        'pixsum' => $pixel,
        'center' => $pixel,
        'pixels' => [$pixel]
    ];
}

function region_update(array & $regions, $ridx, array $pixel)
{
    $regions[$ridx]['nb_pixels']++;
    $regions[$ridx]['pixels'][] = $pixel;
    $regions[$ridx]['pixsum'] = [
        $regions[$ridx]['pixsum'][0] + $pixel[0],
        $regions[$ridx]['pixsum'][1] + $pixel[1]
    ];
    $regions[$ridx]['center'][0] = $regions[$ridx]['pixsum'][0] / $regions[$ridx]['nb_pixels'];
    $regions[$ridx]['center'][1] = $regions[$ridx]['pixsum'][1] / $regions[$ridx]['nb_pixels'];
}

function regions_post_processing(array & $regions)
{
    print 'Post processing regions...' . PHP_EOL;

    foreach ($regions as $region) {
        $region['max_luminance'] = luminance(imagecolorat($base_img, round($region['center'][0]), round($region['center'][1])));

        foreach ($region['pixels'] as $p) {
            $lumi = luminance(imagecolorat($base_img, $p[0], $p[1]));
            if ($lumi > $region['max_luminance']) {
                color2rgb(imagecolorat($base_img, $p[0], $p[1]), $r, $g, $b);
                $region['max_luminance'] = $lumi;
            }
        }
    }

    return $regions;
}

function extract_non_black_pixels_regions($img, $width, $height, array & $regions = array(), $region_max_distance = 4)
{
    $rmds = $region_max_distance * $region_max_distance;

    $ri = null;
    for ($y = 0 ; $y < $height ; $y++) {
        for ($x = 0 ; $x < $width ; $x++) {
            if (imagecolorat($img, $x, $y) === 0) {
                continue;
            }

            $found_region = false;
            if ($ri === null) {
                $ri = 0;
            } else {
                $dx = $x - $regions[$ri]['center'][0];
                $dy = $y - $regions[$ri]['center'][1];
                $dist_from_region = $dx * $dx + $dy * $dy;
                if ($dist_from_region <= $rmds) {
                    $found_region = true;
                } else {
                    foreach ($regions as $ridx => $region) {
                        $dx = $x - $regions[$ri]['center'][0];
                        $dy = $y - $regions[$ri]['center'][1];
                        $dist_from_region = $dx * $dx + $dy * $dy;
                        if ($dist_from_region <= $rmds) {
                            $ri = $ridx;
                            $found_region = true;
                            break;
                        }
                    }
                }
            }

            if ($found_region) {
                region_update($regions, $ri, [$x, $y]);
            } else {
                region_create($regions, [$x, $y], $ri);
            }
        }
    }

    return $regions;
}

function delete_detected_region_pixel(array & $pixels, $dist_from_center, $i)
{
    // print sprintf('deleted %s %s %s (%f, %f)', $dist_from_center, $i, isset($pixels[$dist_from_center][$i]), $pixels[$dist_from_center][$i][0], $pixels[$dist_from_center][$i][1]) . PHP_EOL;
    unset($pixels[$dist_from_center][$i]);

    // var_dump(count($pixels[$dist_from_center]));
    // var_dump(reset($pixels[$dist_from_center]));

    if (false === reset($pixels[$dist_from_center])) {
        unset($pixels[$dist_from_center]);
        // print sprintf('deleted pixels[%s]', $dist_from_center) . PHP_EOL;
    }
}

function detect_close_pixels($width, $height, array & $pixels, $p1, $region_max_distance, array & $region, $depth = 0)
{
    print sprintf('detect_close_pixels (%d, %d) %d/%d', $p1[0], $p1[1], $depth, $region_max_distance). PHP_EOL;
    if ($depth > $region_max_distance) {
        // print 'stopped by max depth'. PHP_EOL;
        return;
    }

    $next_pixels = [];

    $p1_dx = $p1[0] - 0.5*$width;
    $p1_dy = $p1[1] - 0.5*$height;

    $p1_dist = sqrt($p1_dx * $p1_dx + $p1_dy * $p1_dy);

    // print sprintf('# count(pixels) = %d', count($pixels)) . PHP_EOL;

    while (($pixels_at_dist = next($pixels)) && false !== ($dist_from_center = key($pixels))) {
        if ($dist_from_center < floor($p1_dist - $region_max_distance)
            || $dist_from_center > ceil($p1_dist + $region_max_distance)
        ) {
            continue;
        }

        foreach ($pixels_at_dist as $i => $p2) {
            $dx = $p2[0] - $p1[0];
            $dy = $p2[1] - $p1[1];

            if ($dx > $region_max_distance && $dy > $region_max_distance) {
                break;
            }

            $dist = $dx * $dx + $dy * $dy;
            if ($dist > $region_max_distance * $region_max_distance) {
                continue;
            }

            // print sprintf('found in region (%f, %f) (%f, %f)', $p1[0], $p1[1], $p2[0], $p2[1]) . PHP_EOL;

            $region['center'][0] += $p2[0];
            $region['center'][1] += $p2[1];
            if ($dist > $region['max_dist_squared']) {
                $region['max_dist_squared'] = $dist;
            }

            $region['pixels'][] = $p2;
            $region['nb_pixels']++;
            $next_pixels[] = $p2;

            delete_detected_region_pixel($pixels, $dist_from_center, $i);
        }
    }

    print sprintf('count(next_pixels) = %d', count($next_pixels)) . PHP_EOL;

    foreach ($next_pixels as $p2) {
        detect_close_pixels($width, $height, $pixels, $p2, $region_max_distance, $region, $depth + 1);
    }

    print sprintf('end detect_close_pixels (%d, %d)', $p1[0], $p1[1]). PHP_EOL;
}

/**
 * extracts subregions from circular regions
 * $region_max_distance should be applied between pixels
 * not between regions and a non affected pixel
 */
function detect_regions($base_img, $img, $color, $region_max_distance = 4)
{
    $width = imagesx($img);
    $height = imagesy($img);

    print 'Extracting non black pixels regions...' . PHP_EOL;
    extract_non_black_pixels_regions($img, $width, $height, $regions, $region_max_distance);

    while (($pixels_at_dist = reset($pixels)) && false !== ($dist_from_center = key($pixels))
        && ($pixel = reset($pixels_at_dist)) && false !== ($i = key($pixels_at_dist))
    ) {
        delete_detected_region_pixel($pixels, $dist_from_center, $i);

        $region = [
            'center' => $pixel,
            'max_dist_squared' => 0,
            'nb_pixels' => 1,
            'pixels' => [$pixel]
        ];

        print sprintf('Creating region (%f, %f)', $pixel[0], $pixel[1]) . PHP_EOL;

        // print sprintf('detect_regions close pixels (%d, %d)', $pixel[0], $pixel[1]) . PHP_EOL;
        detect_close_pixels($width, $height, $pixels, $pixel, $region_max_distance, $region);

        $region['center'][0] /= $region['nb_pixels'];
        $region['center'][1] /= $region['nb_pixels'];
        $region['max_luminance'] = luminance(imagecolorat($base_img, round($region['center'][0]), round($region['center'][1])));

        $region['max_dist'] = 0;
        foreach ($region['pixels'] as $p) {
            $dx = $p[0] - $region['center'][0];
            $dy = $p[1] - $region['center'][1];
            $dist = sqrt($dx * $dx + $dy * $dy);
            if ($dist > $region['max_dist']) {
                $region['max_dist'] = $dist;
            }

            $lumi = luminance(imagecolorat($base_img, $p[0], $p[1]));
            if ($lumi > $region['max_luminance']) {
                // color2rgb(imagecolorat($base_img, $p[0], $p[1]), $r, $g, $b);
                $region['max_luminance'] = $lumi;
            }
        }

        unset($region['max_dist_squared']);

        $regions[] = $region;
    }

    var_dump($dist_from_center . '-' . $i);
    var_dump(count($pixels_at_dist));
    var_dump($pixel);

    return $regions;
}

function img_regions(& $img, array & $regions)
{
    $red = imagecolorallocate($img, 255, 0, 0);
    $green = imagecolorallocate($img, 0, 255, 0);

    $i = 0;
    $f = 255 * 255 * 255 / count($regions);
    foreach ($regions as $ireg => $reg) {
        unset($regions[$ireg]['pixels']);

        imagesetpixel($img, round($reg['center'][0]), round($reg['center'][1]), $red);

        //$d = ceil(2 * $reg['max_dist']);
        $d = 4;
        imagearc($img, round($reg['center'][0]), round($reg['center'][1]), $d, $d, 0, 360, $reg['max_luminance'] > 118 ? $green : $red);

        $i++;
    }

    return $img;
}

function plot_color(Closure $fun)
{
    $img = imagecreatetruecolor(26*26, 26);

    for ($b = 0; $b <= 25 ; $b++) {
        for ($g = 0; $g <= 25 ; $g++) {
            for ($r = 0; $r <= 25 ; $r++) {
                //print sprintf('(%d,%d) : %d:%d:%d', $g * 26 + $b, $r, $r*10, $g*10, $b*10) . PHP_EOL;
                if ($fun($r*10, $g*10, $b*10)) {
                    $color = imagecolorallocate($img, $r*10, $g*10, $b*10);
                    imagesetpixel($img, $g * 26 + $b, $r, $color);
                }
            }
        }
    }

    imagepng($img, './plot.png');
    imagedestroy($img);
}
