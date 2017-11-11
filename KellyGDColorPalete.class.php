<?php

// todo test with small images

class KellyGDColorPalete
{    
    public $image = false;
    public $imageFile = false;
    
    public $simplify = 25.5; // round founded color to 255 / $simplify , so the maximum color pallet of image may be 255 / $s * 255 / $s * 255 / $s 
    public $chunks = 32; // divide image on chunks and take pixel from each one
    
    public $centerPriority = true; // main colors in center, turn on centerM multipiller for colors founded in center of image
    public $centerM = 4;
    public $foregroundObjectM = 10; // add more importanse to colors that locate in center point of image by width from top to bottom, so we can had chance to detect what color have object in the middle 
    
    public $log = '';
    
    private $palete = array();
    private $readed = false;
    
    public function __construct($imageFile = false) 
    {       
        $this->setImage($imageFile);
    }
    
    public function clear() 
    {        
        if ($this->image and get_resource_type($this->image) == 'gd') imagedestroy($this->image);
        
        $this->image = false;
        return true;
    }

    public function setImage($imageFile)
    {
        if (!$imageFile) return false;
       
        $this->readed = false;
        
        if (sizeof($this->palete) > 0) unset($this->palete);
        $this->palete = array();
        
        if (!is_string($imageFile) and get_resource_type($imageFile) == 'gd') {
            
            $this->image = $imageFile;                       
        } else {     

            $this->imageFile = $imageFile;
        }
        
        return true;
    }
    
    private function loadImage() 
    {
        if ($this->image) return true;
        
        if (!$this->imageFile) {
            $this->log('Please select a file');
            return false;           
        }
        
        if (!is_file($this->imageFile) || !is_readable($this->imageFile)) {
            $this->log('Cannot read image file ' . $this->imageFile . ' - unexist or unreadable');
            return false;
        } 

        $size = getimagesize($this->imageFile);
        if ($size === false) {
            $this->log('Cannot read image file ' . $this->imageFile . ' - bad data');
            return false;
        }

        switch ($size[2]) {
            case 2: $img = imagecreatefromjpeg($this->imageFile);
                break;
            case 3: $img = imagecreatefrompng($this->imageFile);
                break;
            case 1: $img = imagecreatefromgif($this->imageFile);
                break;
            default : return false;
        }

        if (!$img) {
            $this->log('Cannot read image file ' . $this->imageFile . ' - imagecreate function return false');
            return false;
        }
        
        if ($size[2] == 3) imagesavealpha($img, true);
        imagealphablending($img, true);
        
        $this->image = $img;       
        return true;        
    }
    
    public function hex2rgb($hex) 
    {
        $hex = trim($hex);
        
        if (empty($hex)) return false;
        if ($hex[0] == '#') $hex = substr($hex, 1);
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        return array($r, $g, $b);
    }
    
    public function rgb2hex($rgb) 
    {
       $hex = str_pad(dechex($rgb[0]), 2, "0", STR_PAD_LEFT);
       $hex .= str_pad(dechex($rgb[1]), 2, "0", STR_PAD_LEFT);
       $hex .= str_pad(dechex($rgb[2]), 2, "0", STR_PAD_LEFT);

       return (string) $hex;
    }

    function rgb2cmyk($rgb) 
    {
        if (!is_array($rgb) or !sizeof($rgb)) return false;

        $c = (255 - $rgb[0]) / 255.0 * 100;
        $m = (255 - $rgb[1]) / 255.0 * 100;
        $y = (255 - $rgb[2]) / 255.0 * 100;

        $b = min(array($c,$m,$y));
        $c = $c - $b; $m = $m - $b; $y = $y - $b;

        return array($c, $m, $y, $b);
    }

    function cmyk2rgb($cmyk) {
        if (!is_array($cmyk) or ! sizeof($cmyk))
            return false;
 
        $c = $cmyk[0] / 100;
        $m = $cmyk[1] / 100;
        $y = $cmyk[2] / 100;
        $k = $cmyk[3] / 100;

        $r = 1 - ($c * (1 - $k)) - $k;
        $g = 1 - ($m * (1 - $k)) - $k;
        $b = 1 - ($y * (1 - $k)) - $k;

        $r = round($r * 255);
        $g = round($g * 255);
        $b = round($b * 255);

        return array($r, $g, $b);
    }

    private function mixColors($colors) {
        
        $cmyk = array(0, 0, 0, 0);
        $num = 0;
        
        
        foreach ($colors as $hex) {
            $rgb = false;
            $hex = (string) $hex;
            $rgb = $this->hex2rgb($hex);
            if (!$rgb) continue;
            
            $ccymyk = $this->rgb2cmyk($rgb);
            if (!$ccymyk) continue;
            
            $cmyk[0] += $ccymyk[0];
            $cmyk[1] += $ccymyk[1];
            $cmyk[2] += $ccymyk[2];
            $cmyk[3] += $ccymyk[3];
            
            $num++;
        }
        
        if ($num) {
            $cmyk[0] = $cmyk[0] / $num;
            $cmyk[1] = $cmyk[1] / $num;
            $cmyk[2] = $cmyk[2] / $num;
            $cmyk[3] = $cmyk[3] / $num;
        }        
        return (string) $this->rgb2hex($this->cmyk2rgb($cmyk));
    }
    
    /* get middle color frome color pallete of image */
    
    public function getRoundColor($mixNum = 2) {
        $palete = $this->getPalete();
        if (!$palete or !sizeof($palete)) return false;
        
        $paleteColors = array_keys($palete);
        if (sizeof($paleteColors) < 2) {
            return $paleteColors[0];  
        }
        
        if ($mixNum > sizeof($paleteColors)) $mixNum = sizeof($paleteColors);

        return $this->mixColors(array_slice($paleteColors, $mixNum * -1, null, true));
    }
    
    public function getPalete($cutPalete = false, $limitPalete = false) 
    {
        if ($this->readed) return $this->palete;
        if (!$this->loadImage()) return false;
        
        $baseW = imagesx($this->image);
        $baseH = imagesy($this->image); 
        $step = 1;
        
        $centerX = $baseW / 2;
        $centerY = $baseH / 2;
                      
        $max = $baseH;
        if ($baseW > $baseH) $max = $baseW;
        
        if ($max > $this->chunks * 2) {
            $step += floor($max / $this->chunks);
        }
        
        $ix = 0;
        $cycles = 0;
        while($ix < $baseW) {
            
            $iy = 0;
            
            if ($this->centerPriority) {
                // calc raiting of color by calculating way from center by x and y
                $mX = (($ix > $centerX) ? $ix - $centerX : $centerX - $ix) / $centerX;
                $mX = 1 - $mX;
                
                // multiplay raiting if near to center
                if ($mX > 0.8) {
                    $mX = $mX * $this->centerM * $this->foregroundObjectM;
                }
                
            } else $mX = 1;
            
            while($iy < $baseH) {
                
                if ($this->centerPriority) {
                    // same for y
                    $mY = (($iy > $centerY) ? $iy - $centerY : $centerY - $iy) / $centerY;
                    $mY = 1 - $mY;
                    
                    if ($mY > 0.8) $mY = $mY * $this->centerM;
                    
                    
                } else $mY = 1;
                
                // finall rating of founded color
                $m = ($mY + $mX) / 2;
                
                $rgb = imagecolorat($this->image, $ix, $iy);
                
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF; 
                
                $rR = round($r / $this->simplify, 0, PHP_ROUND_HALF_DOWN) * $this->simplify;
                $rG = round($g / $this->simplify, 0, PHP_ROUND_HALF_DOWN) * $this->simplify;
                $rB = round($b / $this->simplify, 0, PHP_ROUND_HALF_DOWN) * $this->simplify;
                
                $hex = (string) $this->rgb2hex(array($rR, $rG, $rB));
                
                if (empty($this->palete[$hex])) $this->palete[$hex] = $m;
                else $this->palete[$hex] += $m;
                
                if ($limitPalete and sizeof($this->palete) >= $limitPalete) {
                    break 2;
                }
                
                $iy = $iy + $step; 
            }                
            
            $ix += $step;
        }
        
        if (sizeof($this->palete)) {
            asort($this->palete);
        }  
        
        if ($cutPalete and sizeof($this->palete) > $cutPalete) {
            $this->palete = array_slice($this->palete, $cutPalete * -1, null, true);
        }         
        
        $this->readed = true;
        return $this->palete;
    }
    
    private function log($text) 
    {
        $this->log .= ' Notice : ' . $text;
    }
    
    private function isSimilarColor($color, $color2, $diff = 5) 
    {
        if ($color >= $color2 - $diff and $color <= $color2 + $diff) return true;
        else return false;
    }
    
    public function comparePaletes($paletes, $referenceKey = 0, $fineDif = 5)
    {
        if (sizeof($paletes) < 2) return false;
        if (!sizeof($paletes[$referenceKey])) return false;
        
        $reference = array_keys($paletes[$referenceKey]);
        // may be better search similar color on the whole color palete?
        // may be add compare middle color
        
        $matchColorsTotal = 0;
        
        foreach($reference as $key => $color) {
            $rColor = $this->hex2rgb($color);
            $matchColors = 0;
            
            foreach ($paletes as $pKey => $colors) {
                if ($referenceKey == $pKey) continue;
                
                if ($colors === false) continue;
                $colors = array_keys($colors);
                if (empty($colors[$key])) continue;
                
                $compareColor = $this->hex2rgb($colors[$key]);
                // search similar in whole color palete
                /*foreach ($colors as $compareColor) {
                    $compareColor = $this->hex2rgb($compareColor);
                    
                    // echo '<br>' . implode(' ', $rColor) . ' vs ' . implode(' ', $compareColor) . '<br>';
                    
                    $similar = true;
                    for ($i = 0; $i <= 2; $i++) {
                        if (!$this->isSimilarColor($rColor[$i], $compareColor[$i], $fineDif)) {
                            $similar = false;
                        }
                    }
                    
                    if ($similar) {
                        $matchColors++;
                        break;
                    }
                }   */  
                
                // echo '<br>' . implode(' ', $rColor) . ' vs ' . implode(' ', $compareColor) . '<br>';
                
                $similar = true;
                for ($i = 0; $i <= 2; $i++) {
                    if (!$this->isSimilarColor($rColor[$i], $compareColor[$i], $fineDif)) {
                        $similar = false;
                    }
                }
                
                if ($similar) {
                    $matchColors++;
                }
            }
            
            $matchColorsTotal += $matchColors / (sizeof($paletes) - 1);
        }
        
        return $matchColorsTotal / sizeof($reference);        
    }
    
    public function testCYMK() {
        $cmyk = $this->rgb2cmyk($this->hex2rgb('1985e5'));
        $rgb = $this->cmyk2rgb($cmyk);
            

        $html = '<div style="'
               . 'background-color : #' . $this->rgb2hex($rgb) . '; '
               . 'display : inline-block; '
               . 'width : 50px; '
               . 'height : 50px; margin-right : 12px; '
               . 'text-align : center;"></div>';            

        return $html;          
    }
    
    // todo смешивать с прозрачностью
    
    public function drawRoundColor($mixNum = 2, $blockSize = 50) 
    {        
        $color = $this->getRoundColor($mixNum);        
        if(!$color) return false;

        $html = '<div style="'
               . 'background-color : #' . $color . '; border : 2px solid rgba(0, 0, 0, 0.2);  '
               . 'display : inline-block; '
               . 'width : ' . $blockSize . 'px; '
               . 'margin : 12px; margin-left : 0px;'
               . 'height : ' . $blockSize . 'px;'
               . 'text-align : center;"></div>';            

        return $html;       
        
    }
    
    public function drawPalete($blockSize = 25) 
    {
        if (!$this->readed) {
            if (!$this->getPalete()) return false;
        }
        
        $html = '';
        
        foreach ($this->palete as $hex => $count) {
            
            $html .= '<div style="'
                   . 'background-color : #' . $hex . '; border : 2px solid rgba(0, 0, 0, 0.2); '
                   . 'display : inline-block; '
                   . 'width : ' . $blockSize .  'px; '
                   . 'height : ' . $blockSize .  'px; margin-right : 12px; '
                   . 'font-size : 12px; '
                   . 'line-height : ' . $blockSize .  'px; '
                   . 'text-align : center;">' . round($count) . '</div>';            
        }
        
        return $html;
    }
}
