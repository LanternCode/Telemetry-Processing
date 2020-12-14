<?php

namespace telemetryProcessing;

class telemetryDataValidator
{
    public function __construct() { }

    public function __destruct() { }

    public function validateSwitches($switches_to_validate = [])
    {
        $checked_switches = [];
        $number_of_switches = 4;
        $allowed_switch_values = [1, 0];

        if (isset($switches_to_validate))
        {
            for($i = 0; $i < $number_of_switches; ++$i)
            {
                if(isset($switches_to_validate[$i]))
                {
                    $validated_switch = filter_var($switches_to_validate[$i], FILTER_SANITIZE_NUMBER_INT);

                    if(in_array($validated_switch, $allowed_switch_values)){
                        $checked_switches[$i] = $validated_switch;
                    }
                    else $checked_switches[$i] = "Unknown";
                }
            }
        }

        return $checked_switches;
    }

    public function validateFan($fan_to_validate)
    {
        $checked_fan = "Unknown";

        if (isset($fan_to_validate))
        {
            $allowed_fan_values = ["forward", "reverse"];
            $sanitised_fan = filter_var($fan_to_validate, FILTER_SANITIZE_STRING);
            if (in_array($sanitised_fan, $allowed_fan_values))
            {
                $checked_fan = $sanitised_fan;
            }
        }
        return $checked_fan;
    }

    public function validateTemperature($temperature_to_check)
    {
        $checked_temperature = "Unknown";

        if (isset($temperature_to_check))
        {
            $minimum_temperature_value = -26;
            $maximum_temperature_value = 26;

            $validated_temperature = filter_var($temperature_to_check, FILTER_VALIDATE_FLOAT);
            if ($validated_temperature >= $minimum_temperature_value && $validated_temperature <= $maximum_temperature_value)
            {
                $checked_temperature = $validated_temperature;
            }
        }
        return $checked_temperature;
    }

    public function validateKeypad($keypad_to_check)
    {
        $checked_keypad = "Unknown";

        if(isset($keypad_to_check))
        {
            $minimum_keypad_value = 0;
            $maximum_keypad_value = 9;

            $sanitised_keypad = filter_var($keypad_to_check, FILTER_SANITIZE_NUMBER_INT);
            $validated_keypad = filter_var($sanitised_keypad, FILTER_VALIDATE_INT);
            if($validated_keypad >= $minimum_keypad_value && $validated_keypad <= $maximum_keypad_value)
            {
                $checked_keypad = $validated_keypad;
            }
        }

        return $checked_keypad;
    }
}