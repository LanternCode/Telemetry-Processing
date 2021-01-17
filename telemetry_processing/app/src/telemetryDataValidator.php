<?php
/**
 * TelemetryDataValidator.php
 *
 * class used to validate data received from M2M server
 *
 * @author Adam Machowczyk, Dominik Michalski
 */
namespace telemetryProcessing;

class TelemetryDataValidator
{
    public function __construct()
    {
    }

    public function __destruct()
    {
    }

    /**
     * Function validates switches
     *
     * @param array $switches_to_validate
     *
     * @return array
     */
    public function validateSwitches($switches_to_validate = [])
    {
        $checked_switches = [];
        $number_of_switches = 4;
        $allowed_switch_values = ["1", "0"];

        if (isset($switches_to_validate)) {
            for ($i = 0; $i < $number_of_switches; ++$i) {
                if (isset($switches_to_validate[$i])) {
                    $sanitized_switch = filter_var($switches_to_validate[$i], FILTER_SANITIZE_NUMBER_INT);
                    $validated_switch = filter_var($sanitized_switch, FILTER_VALIDATE_INT);

                    if (in_array($validated_switch, $allowed_switch_values) && strlen($validated_switch) > 0) {
                        $checked_switches[$i] = $switches_to_validate[$i];
                    } else {
                        $checked_switches[$i] = "NULL";
                    }
                } else {
                    $checked_switches[$i] = "NULL";
                }
            }
        }

        return $checked_switches;
    }

    /**
     * Function validates fan variable
     *
     * @param  $fan_to_validate
     *
     * @return string
     */
    public function validateFan($fan_to_validate)
    {
            $checked_fan = "NULL";

        if (isset($fan_to_validate)) {
            $allowed_fan_values = ["forward", "reverse"];
            $sanitised_fan = filter_var($fan_to_validate, FILTER_SANITIZE_STRING);
            if (in_array($sanitised_fan, $allowed_fan_values)) {
                $checked_fan = $sanitised_fan;
            }
        }
        return $checked_fan;
    }

    /**
     * Function validates temperature variable
     *
     * @param  $temperature_to_check
     *
     * @return int
     */
    public function validateTemperature($temperature_to_check)
    {
        $checked_temperature = "NULL";

        if (isset($temperature_to_check)) {
            $minimum_temperature_value = -26;
            $maximum_temperature_value = 26;

            if ($temperature_to_check >= $minimum_temperature_value
                && $temperature_to_check <= $maximum_temperature_value
            ) {
                $checked_temperature = $temperature_to_check;
            }
        }
        return $checked_temperature;
    }

    /**
     * Function validates keypad variable
     *
     * @param  $keypad_to_check
     *
     * @return int
     */
    public function validateKeypad($keypad_to_check)
    {
        $checked_keypad = "NULL";

        if (isset($keypad_to_check)) {
            $minimum_keypad_value = 0;
            $maximum_keypad_value = 9;

            $sanitised_keypad = filter_var($keypad_to_check, FILTER_SANITIZE_NUMBER_INT);
            $validated_keypad = filter_var($sanitised_keypad, FILTER_VALIDATE_INT);
            if ($validated_keypad >= $minimum_keypad_value
                && $validated_keypad <= $maximum_keypad_value && strlen($validated_keypad) > 0
            ) {
                $checked_keypad = $validated_keypad;
            }
        }

        return $checked_keypad;
    }
}
