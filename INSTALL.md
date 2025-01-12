## Installation

These instructions assume you installed FreeScout using the [recommended process](https://github.com/freescout-helpdesk/freescout/wiki/Installation-Guide), the "one-click install" or the "interactive installation bash-script", and you are viewing this page using a macOS or Ubuntu system.

Other installations are possible, but not supported here.

1. Download the [latest release of UnSub](https://github.com/aarhus/AarhusChangeCustomer).

 ```sh
   wget https://github.com/aarhus/AarhusChangeCustomer/archive/refs/tags/v0.0.11.zip

   # or

   wget https://github.com/aarhus/AarhusChangeCustomer/archive/refs/tags/v0.0.11.tar.gz
```
   

2. Uncompress the file and then rename the directory from AarhusChangeCustomer-v0.0.11 to AarhusChangeCustomer

   ```sh
   tar -xvzf v0.0.11.tar.gz
   # or
   unzip v0.0.11.zip

   # then

   mv AarhusChangeCustomer-0.0.11 AarhusChangeCustomer
   ```

3. Mv the folder to the Modules directory in the web root (i.e. /var/www/html/Modules/ )

   ```sh

   mv AarhusChangeCustomer /var/www/html/Modules/

   ```

4. Make sure that the files are readable by the webserver....

   ```sh
   chown -r www-data:www-data /var/www/html/Modules/AarhusChangeCustomer/
   ```

5. Access your admin modules page like https://freescout.example.com/modules/list.

6. Find **Aarhus Change Customer** and click ACTIVATE.

7. Use and enjoy!

8. [Buy Matt a Coffee](https://ko-fi.com/aarhus)
