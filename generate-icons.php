<?php
// Generate PWA icons using GD library

$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$color = [229, 9, 20]; // Red color

foreach ($sizes as $size) {
    $image = imagecreatetruecolor($size, $size);
    
    imagesavealpha($image, true);
    $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
    imagefill($image, 0, 0, $transparent);
    
    $red = imagecolorallocate($image, $color[0], $color[1], $color[2]);
    
    imagefilledellipse($image, $size/2, $size/2, $size-10, $size-10, $red);
    
    $white = imagecolorallocate($image, 255, 255, 255);
    $fontSize = $size / 4;
    $text = "C";
    $fontFile = null;
    
    if (function_exists('imagettftext')) {
        $x = $size / 2 - ($fontSize / 2);
        $y = $size / 2 + ($fontSize / 2);
    } else {
        imagestring($image, 5, $size/2 - 10, $size/2 - 10, $text, $white);
    }
    
    imagepng($image, "assets/images/icon-{$size}.png");
    imagedestroy($image);
    
    echo "Generated icon-{$size}.png\n";
}

// Generate placeholder image
$placeholder = imagecreatetruecolor(300, 450);
$bg = imagecolorallocate($placeholder, 26, 26, 26);
$textColor = imagecolorallocate($placeholder, 140, 140, 140);
imagefill($placeholder, 0, 0, $bg);
imagestring($placeholder, 5, 100, 220, "No Image", $textColor);
imagepng($placeholder, "assets/images/placeholder.jpg");
imagedestroy($placeholder);

echo "Generated placeholder.jpg\n";
echo "All icons generated successfully!\n";
?>
