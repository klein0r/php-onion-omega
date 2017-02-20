<?php

require_once(__DIR__ . '/OmegaOnion.php');
require_once(__DIR__ . '/Lcd.php');

$gpio = new \MKleine\OnionOmega\Gpio();

$display = new \MKleine\OnionOmega\Lcd(          
    2, 16, 4, 21, 20, 1, 6, 7, 8, 0, 0, 0, 0
);                        
                           
$display->lcdClear();         
$gpio->setRGBled('0xff0000');

foreach ($argv as $line => $value) {
    if ($line > 0) {
        $display->lcdPosition(0, $line - 1);     
        $display->lcdPuts($value);
    }
}

$gpio->setRGBled('0x00ff00');
