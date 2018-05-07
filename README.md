
# Requirements
Requirements: composer, webserver with php7.2, the turtlecoin wallet and mysql.
# Installation
clone this repository into the /var/www/html(Linux) directory or in the htdocs directory(Windows, Mac); The the easiest way to install all librarys for php is running this command: <code>composer require turtlecoin/turtlecoin-walletd-rpc-php</code>.
Create a database named turtlecoin (or you have to change it in the conf.php) and a table named addresses.The table should have this format: id(int), address(varchar), privview(varchar), pubspend(varchar), privspend(varchar), give a proper length to the keys and the address, the id doesn't needs a length and should have auto_increment and unique enabled.
# Using
You are now ready to visit your webserver and do your stuff, but before that we have to start the wallet daemon. You don't have a wallet? run on terminal/cmd <code>./walletd -g -w walletname -p thestrongestpasswordeversonoonecancrackit</code> on Linux/Mac and <code>walletd.exe -g -w walletname -p thestrongestpasswordeversonoonecancrackit</code> on Windows. Have a wallet already(or generated one yet)? you'r on the target line, just run <code>./walletd -w walletname -p thestrongestpasswordeversonoonecancrackit --rpc-password thestrongestpasswordeversonoonecancrackit --daemon-address public.turtlenode.io</code> on Linux/Mac and <code>walletd.exe -w walletname -p thestrongestpasswordeversonoonecancrackit --rpc-password thestrongestpasswordeversonoonecancrackit --daemon-address public.turtlenode.io</code> on Windows.
# Be Happy
Wow you are now ready to get some users, make sure you only present stable releases to your users, test pre-releases on test-servers to avoid dependency issues! If you have too much money: TRTLuxns7wcNqnoBMjYrMEhRTQdq8AKcwi1G58uqfgdiMqhDZS1fyaAenTwKiPgryn5TQNukGkQScdVqExcLj9XE5EZWvw8Y9R5
