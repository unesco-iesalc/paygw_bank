<?php
// This file is part of the bank paymnts module for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Contains class for bank payment gateway.
 *
 * @package   paygw_bank
 * @copyright UNESCO/IESALC
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_bank;
/**
 * The gateway class for bank payment gateway.
 *
 * @copyright UNESCO/IESALC
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends \core_payment\gateway
{
    public static function get_supported_currencies(): array
    {
        // See https://developer.bank.com/docs/api/reference/currency-codes/,
        // 3-character ISO-4217: https://en.wikipedia.org/wiki/ISO_4217#Active_codes.
        $alternatecurrencies=get_config('paygw_bank', 'aditionalcurrencies');
        $alternatecurrencies=trim($alternatecurrencies);
        $altcurrenc=array();
        if(strlen($alternatecurrencies)>2) {
            $altcurrenc=explode(',', $alternatecurrencies);
        }
        $initialcurrencies=[
            'AUD', 'BRL', 'CAD', 'CHF', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'HUF', 'ILS', 'INR', 'JPY',
            'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'RUB', 'SEK', 'SGD', 'THB', 'TRY', 'TWD', 'USD'
        ];
        return array_merge($initialcurrencies, $altcurrenc);
    }

    /**
     * Configuration form for the gateway instance
     *
     * Use $form->get_mform() to access the \MoodleQuickForm instance
     *
     * @param \core_payment\form\account_gateway $form
     */
    public static function add_configuration_to_gateway_form(\core_payment\form\account_gateway $form): void
    {
        $mform = $form->get_mform();
        $mform->addElement('checkbox', 'upload', get_string('instructionstext', 'paygw_bank'));
        $mform->setType('instructionstext', PARAM_RAW);
        $mform->addElement('editor', 'instructionstext', get_string('instructionstext', 'paygw_bank'));
        $mform->setType('instructionstext', PARAM_RAW);
        $mform->addElement('editor', 'postinstructionstext', get_string('postinstructionstext', 'paygw_bank'));
        $mform->setType('postinstructionstext', PARAM_RAW);
        $mform->addElement('text', 'codeprefix', get_string('codeprefix', 'paygw_bank'));
        $mform->setType('codeprefix', PARAM_RAW);
        //add default value to codeprefix
        $mform->setDefault('codeprefix', 'code');
    }

    /**
     * Validates the gateway configuration form.
     *
     * @param \core_payment\form\account_gateway $form
     * @param \stdClass                          $data
     * @param array                              $files
     * @param array                              $errors form errors (passed by reference)
     */
    public static function validate_gateway_form(
        \core_payment\form\account_gateway $form,
        \stdClass $data,
        array $files,
        array &$errors
    ): void {
        if (!$data->enabled) {
            $errors['enabled'] = get_string('gatewaycannotbeenabled', 'payment');
        }
    }
}
