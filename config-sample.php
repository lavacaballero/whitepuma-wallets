<?php
    /**
     * Sample Wallet Daemon Configuration file
     * It must be edited and saved as config-CoinName.php - it should be reflected on .htaccess
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

    class config
    {
        #=====================#
        # Coin daemon options #
        #=====================#

        # Edit this and put the passphrase you've set to your wallet file.
        var $wallet_passphrase = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";

        # Set daemon's RPC info here.
        var $coin_daemon_rpc_info = array(
            "host"        => "localhost",
            "port"        => "xxxx",
            "rpcuser"     => "rpcuser",
            "rpcpassword" => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
        );

        #======================#
        # Your network options #
        #======================#

        # Allowed providers array
        var $allowed_providers = array(
            "provider_keyname" => array(   # Set the provider keyname to something for the entire platform
                "enabled"                   => true,
                "name"                      => "Some name for the provider",
                "public_key"                => "provider_keyname", # The same as above
                "secret_key"                => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
                "allowed_ips"               => '/.*/',     # Should be specified as preg_match pattern
                "fees_account"              => "_txfees",  # The one who will split the fees
                "transaction_fee"           => 0.00000000, # To be moved to fees_account
                "min_transaction_amount"    => 0.00000100, # This is for in-wallet account to account transfer
                "system_transaction_fee"    => 0.00000000, # You should leave this at zero, since the wallet calculates it automatically
                                                           # Yet, you should specify some standardized amount on the backend and frontend
                "withdraw_fee"              => 0.00000000, # To be moved to fees_account when user withdraws (off-chain)
                "min_withdraw_amount"       => 0.00300000, # When going out. withdraw fee must be discounted (system fee is deducted from here)
            )
        );

        #==================#
        # Operation opions #
        #==================#

        # Minimum confirmations allowed to transfer funds out to the wild.
        var $minimum_confirmations = 6;

        # List of allowed commands to be received and forwarded to the daemon.
        var $allowed_commands = array(
            # command              params                                        Notes
            "getnewaddress",     # <account>                                     For incoming registrations
            "getaccountaddress", # <account>                                     Returns the current bitcoin address for receiving payments to this account.
            "getbalance",        # <account> <minconf>                           To get an account balance
            "listtransactions",  # <account> <count>                             Last 100 transactions
            "move",              # <from_account> <to_account>                   Tipping
            "sendfrom",          # <fromaccount> <to_address> <amount> <minconf> Will send the given amount to the given address.
        );
    } # end class

    $config = new config();
