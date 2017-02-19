<?php
namespace MKLeine\OnionOmega;

class Gpio
{
    // format:
    //     %u = unsigned decimal number
    //     %b = integer binary
    //     %s = string
    //     %d = (signed) decimal number

    // fast-gpio set-{in/out}put <gpio>
    const FASTGPIO_SETDIRECTION = 'fast-gpio set-%sput %u';

    // fast-gpio get-direction <gpio>
    const FASTGPIO_GETDIRECTION = 'fast-gpio get-direction %u';

    // fast-gpio set <gpio> <value: 0 or 1>
    const FASTGPIO_SETPIN = 'fast-gpio set %u %b';

    // fast-gpio read <gpio>
    const FASTGPIO_READPIN = 'fast-gpio read %u';

    // fast-gpio pwm <gpio> <freq in Hz> <duty cycle percentage>
    const FASTGPIO_PWMPIN = 'fast-gpio pwm %u %u %u';

    // need RGB color code (output more than 1 row gives problems?)
    const EXP_LED = 'expled %s > /dev/null';

    // relay-exp -s <dipswitch=000> -i;
    const EXP_RELAY_INIT = 'relay-exp -s %s -i';

    // relay-exp -s <dipswitch=000> <channel:0 or 1 or all> <value:0 or 1>
    const EXP_RELAY_SET = 'relay-exp -s %s %s %u';

    const DIRECTION_IN = 'in';
    const DIRECTION_OUT = 'out';

    const VALUE_ON = 1;
    const VALUE_OFF = 0;

    private function execCommand($command)
    {
        $res = exec($command);
        //echo $command . ' -> ' . $res . PHP_EOL;
        return $res;
    }

    public function setGpioDirection($gpio, $direction = self::DIRECTION_IN)
    {
        if ($direction == self::DIRECTION_IN || $direction == self::DIRECTION_OUT) {
            $command = sprintf(self::FASTGPIO_SETDIRECTION, $direction, $gpio);
            $this->execCommand($command);
        }
    }

    public function getGpioDirection($gpio)
    {
        $command = sprintf(self::FASTGPIO_READPIN, $gpio);
        $readOutput = $this->execCommand($command);
        $result = (strpos($readOutput, 'input') === false ? self::DIRECTION_OUT : self::DIRECTION_IN);

        return $result;
    }

    public function readGpio($gpio)
    {
        $command = sprintf(self::FASTGPIO_READPIN, $gpio);
        $readOutput = $this->execCommand($command);

        if (isset($readOutput)) {
            $result = ((substr(rtrim($readOutput), -1) === '1') === false ? 0 : 1);
            return $result;
        }

        return false;
    }

    public function writeGpio($gpio, $value = self::VALUE_OFF)
    {
        if ($value == self::VALUE_ON || $value == self::VALUE_OFF) {
            $command = sprintf(self::FASTGPIO_SETPIN, $gpio, $value);
            return $this->execCommand($command);
        }

        return false;
    }

    public function pwmGpio($gpio, $time, $percentage)
    {
        $command = sprintf(self::FASTGPIO_PWMPIN, $gpio, $time, $percentage);
        return $this->execCommand($command);
    }

    public function setRGBled($value = 'off')
    {
        if ($value == 'off') {
            // write 1="on" to 15/16/17 sets off the rgb led
            $this->writeGpio(15, 1);
            $this->writeGpio(16, 1);
            $this->writeGpio(17, 1);
        } else {
            $command = sprintf(self::EXP_LED, $value);
            $this->execCommand($command);
        }
    }

    public function initRelay($dipSwitch = "000")
    {
        $command = sprintf(self::EXP_RELAY_INIT, $dipSwitch);
        return $this->execCommand($command);
    }

    public function writeRelay($channel = 2, $value = 0, $dipSwitch = "000")
    {
        if ($value === self::VALUE_ON || $value === self::VALUE_OFF) {
            $command = sprintf(self::EXP_RELAY_SET, $dipSwitch, ($channel === 2 ? "all" : $channel), $value);
            return $this->execCommand($command);
        }

        return false;
    }
}
