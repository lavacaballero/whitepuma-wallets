<?php
    /**
     * Common functions from different sources
     *
     * @package    WhitePuma OpenSource Platform
     * @subpackage Wallets endpoint API
     * @copyright  2014 Alejandro Caballero
     * @author     Alejandro Caballero - acaballero@lavasoftworks.com
     * @license    GNU-GPL v3 (http://www.gnu.org/licenses/gpl.html)
     *
     * This program is free software: you can redistribute it and/or modify
     * it under the terms of the GNU General Public License as published by
     * the Free Software Foundation, either version 3 of the License, or
     * (at your option) any later version.
     *
     * This program is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     * GNU General Public License for more details.
     * THE SOFTWARE.
     */

    /**
     * RIJNDAEL_256 decrypter
     *
     * @requires mcrypt package
     * @param string $key
     * @param string $string_to_decrypt
     *
     * @returns mixed
     */
    function decryptRJ256($key, $string_to_decrypt)
    {
        $string_to_decrypt = base64_decode($string_to_decrypt);
        $md5_key = md5($key);
        $iv      = md5($md5_key);
        $rtn     = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $md5_key, $string_to_decrypt, MCRYPT_MODE_CBC, $iv);
        $rtn     = rtrim($rtn, "\0\4");
        return($rtn);
    } # end function

    /**
     * RIJNDAEL_256 encrypter
     *
     * @requires mcrypt package
     * @param mixed  $key
     * @param string $string_to_encrypt
     * @returns string base64 encoded
     */
    function encryptRJ256($key, $string_to_encrypt)
    {
        $md5_key = md5($key);
        $iv      = md5($md5_key);
        $rtn     = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $md5_key, $string_to_encrypt, MCRYPT_MODE_CBC, $iv);
        $rtn     = base64_encode($rtn);
        return($rtn);
    } # end function

    /**
     * Check if the specified address is a valid wallet address
     *
     * @param string $address
     * @returns boolean
     */
    function is_wallet_address($address)
    {
        return preg_match('/[a-zA-Z0-9]{25,34}/', $address);
    } # end function
