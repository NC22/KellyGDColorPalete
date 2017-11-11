<?php
/* WEB-APP : Kelly (С) 2014 NC22 | License : GPLv3 */

error_reporting(E_ALL | E_STRICT); 

$kellyRoot = '../';
require $kellyRoot . 'KellyGDColorPalete.class.php';

function renderPage($center = true, $centerMultiply = 4, $roundColor = 6) {
global $images, $imagePalete, $testDir;
    
    $imagePalete->centerPriority = $center;
    $imagePalete->centerM = $centerMultiply;
    
    $compare = array();

    foreach($images as $image)
    {
        $file = $image;
        
        echo '<div class="test-block"><img class="test-image" src="'.$image.'">';
        echo '<div style="padding-right : 16px;"><div style="border-bottom : 2px dashed rgba(204, 204, 204, 0.75);">' . $file . '</div>';
        
        $imagePalete->setImage($file);
        
        $compare[] = $imagePalete->getPalete(5); // return 6 most used colors in image 
        $palete = $imagePalete->drawPalete(); // format output last generated palete, but you can also get it buy "foreach ($imagePalete->palete as $hex => $count)" instead
        
        if (!$palete) echo $imagePalete->log;
        echo '<br> ' . $palete; // generated html of color palete blocks
        echo '<br> ' . $imagePalete->drawRoundColor($roundColor); // how much colors from color palete we mix together and get "common" color of image
        echo '</div></div>';
        
        $imagePalete->clear(); // always clear image buffer before set new image or on end work 
    }
    
    // echo 'Different : ' . $imagePalete->comparePaletes($compare, 0, 25); not effective, but you can experiment with this option
}

$imagePalete = new KellyGDColorPalete();
$images = glob('*.png');
?>

<html>
<head>
<title>self test</title>
<style>
    html, body {
        height : 100%;
        width : 100%;
        margin : 0px;
        padding : 0px;
    }
    
    body {
        background : rgba(163, 160, 170, 0.4);
    }
    
    .test-image {
        border : 2px solid rgba(31, 21, 21, 0.36);
        width : 200px;
    }
    .test-block {
        display : inline-block; 
        max-width : 320px;
        padding : 6px;
    }
</style>
</head>
<body>

    <?php renderPage(true, 20, 1); ?>

</body>
</html>
