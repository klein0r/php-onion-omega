<?php
namespace MKLeine\OnionOmega;

use \MKLeine\OnionOmega\Gpio;

require_once(__DIR__ . '/OmegaOnion.php');

class Lcd
{
    // HD44780U Commands
    const LCD_CLEAR = 0x01;
    const LCD_HOME = 0x02;
    const LCD_ENTRY = 0x04;
    const LCD_CTRL = 0x08;
    const LCD_CDSHIFT = 0x10;
    const LCD_FUNC = 0x20;
    const LCD_CGRAM = 0x40;
    const LCD_DGRAM = 0x80;
    // Bits in the entry register
    const LCD_ENTRY_SH = 0x01;
    const LCD_ENTRY_ID = 0x02;
    // Bits in the control register
    const LCD_BLINK_CTRL = 0x01;
    const LCD_CURSOR_CTRL = 0x02;
    const LCD_DISPLAY_CTRL = 0x04;
    // Bits in the function register
    const LCD_FUNC_F = 0x04;
    const LCD_FUNC_N = 0x08;
    const LCD_FUNC_DL = 0x10;
    const LCD_CDSHIFT_RL = 0x04;
    // Helpers
    private $lcdControl;
    private $gpio;
    // Parameters
    private $rows;
    private $cols;
    private $bits;
    private $rs;
    private $strb;
    private $dataPins;
    // Position
    private $cx = 0;
    private $cy = 0;
    private $rowOff = array(
        0x00,
        0x40,
        0x14,
        0x54
    );

    public function __construct(
        $rows, $cols, $bits,
        $rs, $strb,
        $d0, $d1, $d2, $d3,
        $d4, $d5, $d6, $d7
    ) {
        $this->gpio = new Gpio();

        if (!($bits == 4 || $bits == 8)) {
            throw new \Exception('Init failed');
        }

        if ($rows < 0 || $rows > 20) {
            throw new \Exception('Init failed');
        }

        if ($cols < 0 || $cols > 20) {
            throw new \Exception('Init failed');
        }

        $this->rows = $rows;
        $this->cols = $cols;
        $this->bits = 8;
        $this->rs = $rs;
        $this->strb = $strb;
        $this->dataPins = array(
            $d0,
            $d1,
            $d2,
            $d3,
            $d4,
            $d5,
            $d6,
            $d7,
        );

        $this->gpio->writeGpio($this->rs, Gpio::VALUE_OFF);
        $this->gpio->setGpioDirection($this->rs, Gpio::DIRECTION_OUT);

        $this->gpio->writeGpio($this->strb, Gpio::VALUE_OFF);
        $this->gpio->setGpioDirection($this->strb, Gpio::DIRECTION_OUT);

        for ($i = 0; $i < $bits; ++$i) {
            $this->gpio->writeGpio($this->dataPins[$i], Gpio::VALUE_OFF);
            $this->gpio->setGpioDirection($this->dataPins[$i], Gpio::DIRECTION_OUT);
        }
        $this->delay(35);

        if ($bits == 4) {
            $func = self::LCD_FUNC | self::LCD_FUNC_DL; // Set 8-bit mode 3 times
            $this->put4Command($func >> 4);
            $this->delay(35);
            $this->put4Command($func >> 4);
            $this->delay(35);
            $this->put4Command($func >> 4);
            $this->delay(35);

            $func = self::LCD_FUNC; // 4th set: 4-bit mode
            $this->put4Command($func >> 4);
            $this->delay(35);
            $this->bits = 4;
        } else {
            $func = self::LCD_FUNC | self::LCD_FUNC_DL;
            $this->putCommand($func);
            $this->delay(35);
            $this->putCommand($func);
            $this->delay(35);
            $this->putCommand($func);
            $this->delay(35);
        }

        if ($this->rows > 1) {
            $func |= self::LCD_FUNC_N;
            $this->putCommand($func);
            $this->delay(35);
        }

        // Rest of the initialisation sequence

        $this->lcdDisplay(true);
        $this->lcdCursor(false);
        $this->lcdCursorBlink(false);
        $this->lcdClear();

        $this->putCommand(self::LCD_ENTRY | self::LCD_ENTRY_ID);
        $this->putCommand(self::LCD_CDSHIFT | self::LCD_CDSHIFT_RL);
    }

    private function lcdDisplay($state)
    {
        if ($state) {
            $this->lcdControl |= self::LCD_DISPLAY_CTRL;
        } else {
            $this->lcdControl &= ~self::LCD_DISPLAY_CTRL;
        }

        $this->putCommand(self::LCD_CTRL | $this->lcdControl);
    }

    private function lcdCursor($state)
    {
        if ($state) {
            $this->lcdControl |= self::LCD_CURSOR_CTRL;
        } else {
            $this->lcdControl &= ~self::LCD_CURSOR_CTRL;
        }

        $this->putCommand(self::LCD_CTRL | $this->lcdControl);
    }

    private function lcdCursorBlink($state)
    {
        if ($state) {
            $this->lcdControl |= self::LCD_BLINK_CTRL;
        } else {
            $this->lcdControl &= ~self::LCD_BLINK_CTRL;
        }

        $this->putCommand(self::LCD_CTRL | $this->lcdControl);
    }

    private function lcdHome()
    {
        $this->putCommand(self::LCD_HOME);
        $this->cx = $this->cy = 0;
        $this->delay(5);
    }

    public function lcdClear()
    {
        $this->putCommand(self::LCD_CLEAR);
        $this->putCommand(self::LCD_HOME);
        $this->cx = $this->cy = 0;
        $this->delay(5);
    }

    public function lcdPosition($x, $y)
    {
        if (($x > $this->cols) || ($x < 0)) {
            return;
        }

        if (($y > $this->rows) || ($y < 0)) {
            return;
        }

        $this->putCommand($x + (self::LCD_DGRAM | $this->rowOff[$y]));

        $this->cx = $x;
        $this->cy = $y;
    }

    private function delay($milliseconds)
    {
        usleep($milliseconds * 1000);
    }

    private function strobe()
    {
        // Note timing changes for new version of delayMicroseconds ()
        $this->gpio->writeGpio($this->strb, 1);
        usleep(50);
        $this->gpio->writeGpio($this->strb, 0);
        usleep(50);
    }

    private function putCommand($command)
    {
        $this->gpio->writeGpio($this->rs, Gpio::VALUE_OFF);
        $this->sendDataCmd($command);
        $this->delay(2);
    }

    private function put4Command($command)
    {
        $this->gpio->writeGpio($this->rs, Gpio::VALUE_OFF);

        for ($i = 0; $i < 4; ++$i) {
            $this->gpio->writeGpio($this->dataPins [$i], ($command & 1));
            $command >>= 1 ;
        }
        $this->strobe();
    }

    private function sendDataCmd($data)
    {
        if ($this->bits == 4) {
            $d4 = ($data >> 4) & 0x0F;
            for ($i = 0; $i < 4; ++$i) {
                $this->gpio->writeGpio($this->dataPins[$i], ($d4 & 1));
                $d4 >>= 1;
            }
            $this->strobe();

            $d4 = $data & 0x0F;
            for ($i = 0; $i < 4; ++$i) {
                $this->gpio->writeGpio($this->dataPins[$i], ($d4 & 1));
                $d4 >>= 1;
            }
        } else {
            for ($i = 0; $i < 8; ++$i) {
                $this->gpio->writeGpio($this->dataPins[$i], ($data & 1));
                $data >>= 1;
            }
        }

        $this->strobe();
    }

    private function lcdCharDef($index, $data)
    {
        $this->putCommand(self::LCD_CGRAM | (($index & 7) << 3));

        $this->gpio->writeGpio($this->rs, Gpio::VALUE_ON);
        for ($i = 0; $i < 8; ++$i) {
            $this->sendDataCmd($data[$i]);
        }
    }

    private function lcdPutchar($data)
    {
        $this->gpio->writeGpio($this->rs, Gpio::VALUE_ON);
        $this->sendDataCmd($data);

        if (++$this->cx == $this->cols) {
            $this->cx = 0;
            if (++$this->cy == $this->rows) {
                $this->cy = 0;
            }

            $this->putCommand($this->cx + (self::LCD_DGRAM | $this->rowOff[$this->cy]));
        }
    }

    public function lcdPuts($string)
    {
        $chars = str_split($string);
        foreach($chars as $char){
            $this->lcdPutchar(ord($char));
        }
    }
}
