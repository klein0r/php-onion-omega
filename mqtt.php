<?php

require_once(__DIR__ . '/phpMQTT.php');

$mqtt = new phpMQTT('192.168.178.11', 1883, "onion-mqtt-display");
while(!$mqtt->connect()) {
    sleep(5);
}

$line1 = ' ';
$line2 = ' ';

$topics['/Onion/Line1'] = array("qos" => 0, "function" => "procmsg");
$topics['/Onion/Line2'] = array("qos" => 0, "function" => "procmsg");
$mqtt->subscribe($topics, 0);
while ($mqtt->proc()) {}
$mqtt->close();

function procmsg($topic, $msg) {
    global $line1;
    global $line2;

    $refresh = false;

    if (strpos($topic, 'Line1') !== false) {
        if ($msq !== $line1) {
            $refresh = true;
        }
        $line1 = $msg;
    }

    if (strpos($topic, 'Line2') !== false) {
        if ($msq !== $line2) {              
            $refresh = true;                
        }
        $line2 = $msg;
    }

    if ($refresh) {
        echo 'aktualisiere display' . PHP_EOL;
        exec('php-cli /root/onion-php/display.php "' . $line1 . '" "' . $line2 . '"');
    }
}
