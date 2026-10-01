# NOVA, a minimal Framework for PHP
To get your database connection up and running, go to /config/config.ini and drop your DB credentials in there. Note that Right now, the setup only supports MySQL. More database options are on the roadmap, so stay tuned!

Before going live, set `DEBUG` to `false` in index.php. Visitors then only see a generic error message, the details go to the PHP error log.

Terminal: nova is part of the framework. You run it from the project folder on the server, for example "php nova make Product", and it creates the controller, model and view for you.
Example Usage:
"php nova make Product"                    # controller Product, model ProductModel (table "product"), view product/show.phtml
"php nova make ShopItem"                   # table defaults to "shop_item", page at /shopItem
"php nova make Customers --table=customer" # table name that differs from the class name
"php nova make About --no-model"           # plain page without a database
"php nova help"
If you have got ideas or want to help make the NOVA-Framework better, contributions are totally welcome! 
Just a quick note: this framework is meant to give web developers a simple, no-fuss starting point for building new websites, without relying on big, heavy frameworks or libraries.


This project is licensed under the **GNU LGPL v2.1**.  
You may use, modify, and distribute it under the terms of this license.  
Modifications must remain open-source under the same license.  

Copyright (C) 2026 Hamzah Mansor
