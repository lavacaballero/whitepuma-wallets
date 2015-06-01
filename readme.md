# WhitePuma Open Source Platform - Wallets endpoint

This project is the lower layer of the platform. You need to put it
in the same host where you have your wallet daemon.

The scripts on this project are called only by the Backend scripts,
so you need to secure the host were you're going to deploy them.

Please check the next repos for more information:

* [Platform Backend](https://github.com/lavacaballero/whitepuma-backend):
  the "man in the middle" to keep this API free of DDOS attacks.
  
* [Platform Frontend](https://github.com/lavacaballero/whitepuma-frontend):
  the pages visited by the users and all scripts doing the sending/receiving
  job for the users.

## Requirements

* Apache 2 with rewrite module
* PHP 5.3+ with mcrypt

## Installation

1. Setup your coin wallet on the host. Configure the RPC server on the wallet
   to allow access _only_ from localhost.

2. Secure the host! You should allow SSH access only from specific IPs
   and use SSH keys instead of passwords.
   
3. Mount a firewall so port 80 is only accessible by the Backend IP.
   If you don't know how, the configuration file can do the trick for you.

4. Install Apache and PHP.

5. Rename the `.htaccess-sample` file to `.htaccess` and add the coin alias RewriteRule. Sample provided.

6. Rename the `config-sample.php` file to `config.php` using the exact name you specified on .htaccess.

7. Upload these scripts and configure a virtual host on Apache to serve the pages.

8. Visit http://your.ip/CoinName/ and you will get a nasty "ERROR:NO_PUBLIC_KEY_PROVIDED" message.
   If so, then you're ready to start receiving requests from the Backend.

## Usage

On the `config-sample.php` you will find all the info you need to set in order to permit communication with
the wallet from the Backend API.

You may never need to manually visit this API since it only listens calls from the backend scripts. Yet, you will
constantly need to login with SSH to give maintenance to the wallet.

Communication scheme is the next:

The Frontend script requests the user's balance to the Backend API with a POST request:

```
http://your.backend.ip/CoinName/?public_key=whatever_you_set&action=get_balance&account=account_id
```

The Backend validates the info and forwards the request to this side with another POST request:

```
http://your.host.ip/CoinName/?public_key=whatever_you_set&action=get_balance&account=account_id
```

Then the Backend communicates with the wallet through a JSON RPC call using the
[EasyBitcoin](https://github.com/aceat64/EasyBitcoin-PHP) utility included and returns either the data in an
encrypted format or some error message.

## Contributing

**Maintainers are needed!** If you want to maintain this repository, feel free to let me know.
I don't have enough time to attend pull requests, so you're invited to be part of the core staff
behind this source.

## Credits

Author: Alejandro Caballero - acaballero@lavasoftworks.com

## License

This project is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This project is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
