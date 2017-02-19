<?php
require_once('OmegaOnion.php');
require_once('Lcd.php');

$test = new \MKleine\OnionOmega\Lcd(          
    2, 16, 4, 21, 20, 1, 6, 7, 8, 0, 0, 0, 0
);                        
                           
$test->lcdClear();         
                                 
$test->lcdPosition(0, 0);     
$test->lcdPuts('FHEM + MQTT');
                                   
$test->lcdPosition(0, 1);       
$test->lcdPuts('ist cool :)');   
