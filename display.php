<?php
require_once('OmegaOnion.php');
require_once('Lcd.php');

$display = new \MKleine\OnionOmega\Lcd(          
    2, 16, 4, 21, 20, 1, 6, 7, 8, 0, 0, 0, 0
);                        
                           
$display->lcdClear();         

foreach ($argv as $line => $value) {
    if ($line > 0) {
        $display->lcdPosition(0, $line - 1);     
        $display->lcdPuts($value);
    }
}
