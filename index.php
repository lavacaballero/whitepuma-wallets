<?php
    /**
     * API executive
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
     *
     * @param           string  $public_hey  Keyname of the client connecting here
     * @param encrypted string  $command     As mentioned in config::$allowed_commands
     * @param encrypted string  $account     User account identifier
     * @param encrypted string  $target      account|address to transfer $amount to
     * @param encrypted mixed   $amount      all|number Amount to transfer or withdraw
     * @param encrypted string  $is_fee      "true" or "false" to specify if there's a move operation mimicking a fee
     *
     * @returns (json) { message: "message", data: (encrypted) (json) data }
     *
     * Otuput errors:
     *  • ERROR:COIN_NAME_NOT_SPECIFIED                  ~ As-is
     *  • ERROR:NO_PUBLIC_KEY_PROVIDED                   ~ As-is
     *  • ERROR:INVALID_PUBLIC_KEY                       ~ Provided public key isn't in allowed clients list
     *  • ERROR:DISABLED_PROVIDER_HOST                   ~ Client provider is disabled here.
     *  • ERROR:NO_CONFIG_FILE_PRESENT                   ~ When there is no config.php file present
     *  • ERROR:INCORRECT_CALLING_METHOD                 ~ Sent GET, not POST
     *  • ERROR:ACCESS_DENIED:<ip_address>               ~ The IP address is not defined in config.php
     *  • ERROR:NULL_COMMAND_SPECIFIED                   ~ As-is
     *  • ERROR:NO_ACCOUNT_KEY_SPECIFIED                 ~ As-is
     *  • ERROR:INVALID_COMMAND                          ~ Passed command is not defined in config.php
     *  • ERROR:INVALID_SYNTAX:TARGET_AND_AMOUNT_NEEDED  ~ When a move|withdrow is requested, account|address and amount must be passed
     *  • ERROR:INVALID_TARGET                           ~ Target address is not a coin address
     *  • ERROR:INVALID_AMOUNT                           ~ Amount is empty or not numeric
     *  • ERROR:UNREGISTERED_ACCOUNT                     ~ Source account not registered here
     *  • ERROR:UNREGISTERED_TARGET_ACCOUNT              ~ Target account not registered here
     *  • ERROR:MINIMUM_TX_AMOUNT:<amount>               ~ Specified transfer is below minimum
     *  • ERROR:MINIMUM_WITHDRAW_AMOUNT:<amount>         ~ Specified withdraw is below minimum
     *  • ERROR:INVALID_BALANCE_RETURNED:<result>        ~ Couldn't get the balance of the ccount.
     *  • ERROR:NOT_ENOUGH_FUNDS                         ~ Source account's funds can't cover transaction
     *  • ERROR:MOVE:CANNOT_APPLY_TX_FEE                 ~ Can't apply transaction fee on a move from account to accoutn
     *  • ERROR:MOVE:CANNOT_APPLY_WITHDRAW_FEE           ~ Can't apply local withdraw fee on withdraw
     *  • ERROR:MOVE:CANNOT_TRANSFER_FUNDS               ~ Problem transferring funds from account 1 to account 2.
     *  • ERROR:ON_COMMAND                               ~ Error when sending command. Info on extended_info field.
     *
     * Valid data output (message = "OK") per command:
     *  • <getnewaddress>        := (string)         account address
     *  • <getaccountaddress>    := (string)         first account address found for the account (others are ignored)
     *  • <getbalance>           := (number)         current account balance
     *  • <listtransactions>     := (reversed array) [account, address, category, amount, confirmations, blockhash, blockindex, blocktime, txid, time, timereceived]
     *  • <move>                 := (number)         new balance
     *  • <sendfrom>             := (string)         transaction id
     */

    #############
    # Bootstrap #
    #############

    header("Content-Type: application/json; charset=utf-8");
    if( empty($_REQUEST["coin_name"]) ) die(json_encode( (object) array("message" => "ERROR:COIN_NAME_NOT_SPECIFIED")  ));
    $coin_name = trim(stripslashes($_REQUEST["coin_name"]));
    if( ! is_file("config-$coin_name.php") ) die(json_encode( (object) array("message" => "ERROR:NO_CONFIG_FILE_PRESENT") ));
    include "config-$coin_name.php";
    include "functions.php";
    include "easybitcoin.php";

    if( empty($_POST) ) die(json_encode( (object) array("message" => "ERROR:INCORRECT_CALLING_METHOD") ));

    if( empty($_POST["public_key"]) ) die(json_encode( (object) array("message" => "ERROR:NO_PUBLIC_KEY_PROVIDED") ));

    $host_data = $config->allowed_providers[$_POST["public_key"]];
    if( empty($host_data) ) die(json_encode( (object) array("message" => "ERROR:INVALID_PUBLIC_KEY") ));
    if( ! $host_data["enabled"] ) die(json_encode( (object) array("message" => "ERROR:DISABLED_PROVIDER_HOST") ));

    $requesting_host = $_SERVER["REMOTE_ADDR"];
    $pattern         = $host_data["allowed_ips"];
    preg_match($pattern, $_SERVER["REMOTE_ADDR"], $matches);
    if( empty($matches) ) die(json_encode( (object) array("message" => "ERROR:ACCESS_DENIED:$requesting_host") ));

    ####################################
    # Params decryption and validation #
    ####################################

    $params = array();
    foreach( $_POST as $key => $val )
    {
        $val = trim($val);
        $params[$key] = decryptRJ256($host_data["secret_key"], $val);
    } # end foreach
    unset($params["public_key"]);

    $command = $params["command"]; unset($params["command"]);
    if( empty($command) )
        die(json_encode( (object) array("message" => "ERROR:NULL_COMMAND_SPECIFIED") ));

    if( empty($params["account"]) )
        die(json_encode( (object) array("message" => "ERROR:NO_ACCOUNT_KEY_SPECIFIED") ));

    if( ! in_array($command, $config->allowed_commands) )
        die(json_encode( (object) array("message" => "ERROR:INVALID_COMMAND") ));

    if( $command == "move" && (empty($params["target"]) || empty($params["amount"])) )
        die(json_encode( (object) array("message" => "ERROR:INVALID_SYNTAX:TARGET_AND_AMOUNT_NEEDED") ));

    if( $command == "sendfrom" && (empty($params["target"]) || empty($params["amount"])) )
        die(json_encode( (object) array("message" => "ERROR:INVALID_SYNTAX:TARGET_AND_AMOUNT_NEEDED") ));

    if( $command == "sendfrom" && ! empty($params["target"]) )
        if( ! is_wallet_address($params["target"]) )
            die(json_encode( (object) array("message" => "ERROR:INVALID_TARGET") ));

    if( ! empty($params["amount"]) && ! (is_numeric($params["amount"]) || $params["amount"] == "all") )
        die(json_encode( (object) array("message" => "ERROR:INVALID_AMOUNT") ));

    #######################
    # Forging & execution #
    #######################

    if( substr($params["account"], 0, 1) == "!" )
        $params["account"] = str_replace("!", "", $params["account"]);
    else
        $params["account"] = $host_data["public_key"] . "." . $params["account"];

    $daemon = new Bitcoin( $config->coin_daemon_rpc_info["rpcuser"]    ,
                           $config->coin_daemon_rpc_info["rpcpassword"],
                           $config->coin_daemon_rpc_info["host"]       ,
                           $config->coin_daemon_rpc_info["port"]       );

    #===============#
    # Account stuff #
    #===============#

    if($command == "getnewaddress")
    {
        #------------------------------------------------------------------------
        # Check if the account already exists. If so, let's return the first one.
        #------------------------------------------------------------------------

        $account_address = $daemon->getaddressesbyaccount(array($params["account"]));
        if( count($account_address) == 1 )
            die(json_encode( (object) array("message" => "OK", "data" => json_encode(encryptRJ256($host_data["secret_key"], $account_address[0]))) ));
        elseif( count($account_address) > 1 )
            die(json_encode( (object) array("message" => "OK", "data" => json_encode(encryptRJ256($host_data["secret_key"], array_pop($account_address)))) ));
    } # end if

    if( $command == "getaccountaddress" )
    {
        #-------------------------------------------------------------------
        # Check if the account already exists. If not, we die with an error.
        #-------------------------------------------------------------------

        $account_address = $daemon->getaddressesbyaccount(array($params["account"]));
        if( count($account_address) == 0 )
            die(json_encode( (object) array("message" => "ERROR:UNREGISTERED_ACCOUNT") ));
        elseif( count($account_address) == 1 )
            die(json_encode( (object) array("message" => "OK", "data" => json_encode(encryptRJ256($host_data["secret_key"], $account_address[0]))) ));
        elseif( count($account_address) > 1 )
            die(json_encode( (object) array("message" => "OK", "data" => json_encode(encryptRJ256($host_data["secret_key"], array_pop($account_address)))) ));
    } # end if

    if( $command == "move" )
    {
        #--------
        # Presets
        #--------

        $params["target"] = $host_data["public_key"] . "." . $params["target"];

        #---------------
        # Check accounts
        #---------------

        $account_address = $daemon->getaddressesbyaccount(array($params["account"]));
        if( count($account_address) == 0 )
            die(json_encode( (object) array("message" => "ERROR:UNREGISTERED_ACCOUNT") ));
        if( count($account_address) == 1 )
            $account_address = $account_address[0];
        elseif( count($account_address) > 1 )
            $account_address = array_pop($account_address);

        $target_address = $daemon->getaddressesbyaccount(array($params["target"]));
        if( count($target_address) == 0 )
            die(json_encode( (object) array("message" => "ERROR:UNREGISTERED_TARGET_ACCOUNT") ));
        if( count($target_address) == 1 )
            $target_address = $target_address[0];
        elseif( count($target_address) > 1 )
            $target_address = array_pop($target_address);

        #---------------
        # Check minimums
        #---------------

        $balance = $daemon->getbalance(array($params["account"], $config->minimum_confirmations));
        if( ! is_numeric($balance) )
            die(json_encode( (object) array("message" => "ERROR:INVALID_BALANCE_RETURNED:$balance") ));
        if($params["amount"] == "all")
        {
            if($params["account"] == $host_data["fees_account"]) $params["amount"] = $balance;
            else                                                 $params["amount"] = $balance - $host_data["transaction_fee"];
        } # end if

        if( $params["is_fee"] != "true" )
            if( $params["account"] != $host_data["fees_account"] && $params["amount"] < $host_data["min_transaction_amount"] )
                die(json_encode( (object) array("message" => "ERROR:MINIMUM_TX_AMOUNT:" . $host_data["min_transaction_amount"]) ));

        #--------------
        # Check balance
        #--------------

        if( ($balance - $host_data["transaction_fee"]) < $params["amount"] )
            die(json_encode( (object) array("message" => "ERROR:NOT_ENOUGH_FUNDS") ));

        #----------------------------------------
        # Do transfer from account 1 to account 2
        #----------------------------------------

        $trans_params = array($params["account"], $params["target"], (float) $params["amount"]);
        $res = $daemon->move($trans_params);
        if( ! $res ) die(json_encode( (object) array("message" => "ERROR:MOVE:CANNOT_TRANSFER_FUNDS", "extended_info" => (object) array("params_sent" => $trans_params, "returned_message" => htmlspecialchars($daemon->error)) ) ));

        #-----------------------------------------------
        # Check for any transactions fees and apply them
        #-----------------------------------------------------------------------------------------------
        if( $params["account"] != $host_data["fees_account"] && ! empty($host_data["transaction_fee"]) )
        #-----------------------------------------------------------------------------------------------
        {
            # We get the fee to our internal fee ppol
            $trans_params = array($params["account"], $host_data["fees_account"], $host_data["transaction_fee"], 1, "Transaction Fee");
            $res = $daemon->move($trans_params);
            if( ! $res ) die(json_encode( (object) array("message" => "ERROR:MOVE:CANNOT_APPLY_TX_FEE", "extended_info" => (object) array("params_sent" => $trans_params, "returned_message" => htmlspecialchars($daemon->error)) ) ));
        } # end if

        # Send new balance
        $balance = $daemon->getbalance(array($params["account"], $config->minimum_confirmations));
        $balance = encryptRJ256($host_data["secret_key"], $balance);
        echo json_encode( (object) array("message" => "OK", "data" => $balance) );
    } # end if

    #===========#
    # Withdraws #
    #===========#

    if( $command == "sendfrom" )
    {
        #--------------
        # Check account
        #--------------

        $account_address = $daemon->getaddressesbyaccount(array($params["account"]));
        if( count($account_address) == 0 )
            die(json_encode( (object) array("message" => "ERROR:UNREGISTERED_ACCOUNT", "extended_info" => $params["account"]) ));
        if( count($account_address) == 1 )
            $account_address = $account_address[0];
        elseif( count($account_address) > 1 )
            $account_address = array_pop($account_address);

        #---------------
        # Check minimums
        #---------------

        $balance = $daemon->getbalance(array($params["account"], 6));
        if( ! is_numeric($balance) )
            die(json_encode( (object) array("message" => "ERROR:INVALID_BALANCE_RETURNED:$balance") ));
        if($params["amount"] == "all")
        {
            if($params["account"] == $host_data["fees_account"]) $params["amount"] = $balance;
            else                                                 $params["amount"] = $balance - $host_data["withdraw_fee"];
        } # end if

        if( $params["account"] != $host_data["fees_account"] && $params["amount"] < $host_data["min_withdraw_amount"] )
            die(json_encode( (object) array("message" => "ERROR:MINIMUM_WITHDRAW_AMOUNT:" . $host_data["min_withdraw_amount"]) ));

        #--------------
        # Check balance
        #-----------------

        if( $params["account"] != $host_data["fees_account"] && ($balance - $host_data["withdraw_fee"]) < $params["amount"] )
            die(json_encode( (object) array("message" => "ERROR:NOT_ENOUGH_FUNDS", "extra_info" => (object) array("balance:" => $balance)) ));

        #----------------------------------------
        # Do transfer from account 1 to account 2
        #----------------------------------------

        # Unlock first...
        $trans_params = array($config->wallet_passphrase, 60);
        $res = $daemon->walletpassphrase($trans_params);
        if( ! empty($res) ) die(json_encode( (object) array("message" => "ERROR:CANNOT_UNLOCK_WALLET", "extended_info" => (object) array("returned_message" => htmlspecialchars($daemon->error)) ) ));

        # Set txfee
        $res = $daemon->settxfee(array($host_data["system_transaction_fee"]));
        # if( ! empty($res) ) die(json_encode( (object) array("message" => "ERROR:CANNOT_SET_TX_FEE", "extended_info" => (object) array("returned_message" => htmlspecialchars($daemon->error)) ) ));

        # Proceed
        $trans_params = array($params["account"], $params["target"], (float) ($params["amount"] - $host_data["withdraw_fee"] - $host_data["system_transaction_fee"]), $config->minimum_confirmations);
        $res = $daemon->sendfrom($trans_params);
        if( ! $res ) die(json_encode( (object) array("message" => "ERROR:SENDFROM:CANNOT_TRANSFER_FUNDS", "extended_info" => (object) array("params_sent" => $trans_params, "returned_message" => htmlspecialchars($daemon->error)) ) ));

        #-----------------------------------------------
        # Check for any transactions fees and apply them
        #--------------------------------------------------------------------------------------------
        if( $params["account"] != $host_data["fees_account"] && ! empty($host_data["withdraw_fee"]) )
        #--------------------------------------------------------------------------------------------
        {
            # We get the fee to our internal fee ppol
            $trans_params = array($params["account"], $host_data["fees_account"], $host_data["withdraw_fee"], 1, "Withdraw fee");
            $resx = $daemon->move($trans_params);
            if( ! $resx ) die(json_encode( (object) array("message" => "ERROR:MOVE:CANNOT_APPLY_WITHDRAW_FEE", "extended_info" => (object) array("params_sent" => $trans_params, "returned_message" => htmlspecialchars($daemon->error)) ) ));
            # $params["amount"] -= $host_data["withdraw_fee"];
        } # end if

        # Send the TX id
        if( is_object($res) || is_array($res) ) $res = json_encode($res);
        $res    = encryptRJ256($host_data["secret_key"], $res);
        echo json_encode( (object) array("message" => "OK", "data" => $res) );
        # $resy = $daemon->keypoolrefill();
        die();
    } # end if

    #================================#
    # Rest of commands (informative) #
    #================================#

    if($command == "getbalance")
    {
        $params["minconf"] = $config->minimum_confirmations;
    } # end if

    if($command == "listtransactions")
    {
        $params["count"] = 1024;
    } # end if

    $params = array_values($params);
    $res    = $daemon->$command($params);
    if( $res->error ) die(json_encode( (object) array("message" => "ERROR:ON_COMMAND", "extended_info" => (object) array("command" => $command, "params_sent" => $trans_params, "returned_message" => htmlspecialchars($daemon->error)) ) ));
    if( is_object($res) || is_array($res) )
    {
        if($command == "listtransactions") $res = array_reverse($res);
        $res = json_encode($res);
    }
    $res    = encryptRJ256($host_data["secret_key"], $res);
    echo      json_encode( (object) array("message" => "OK", "data" => $res) );
