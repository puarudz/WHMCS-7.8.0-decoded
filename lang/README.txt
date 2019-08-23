===============================================================================
WHMCS - The Complete Client Management, Billing & Support Solution
===============================================================================

            Managing Language Files with Overrides

      Documentation: http://docs.whmcs.com/Language_Files

===============================================================================
[ CONTENTS ]
===============================================================================

1. Overview
2. Usage Instructions

===============================================================================
[ OVERVIEW ]
===============================================================================

These language files are provided unencoded to allow you to view the language
strings that WHMCS uses.

We do not recommend editing these files directly. They are often overwritten
during software upgrades to introduce new and updated text.

Instead we recommend that you use overrides which allow you to customise the
default variables with your own in a way which can be safely preserved through
the upgrade process.

===============================================================================
[ USAGE INSTRUCTIONS ]
===============================================================================

Steps for customising language strings via overrides are as follows:

1. Create the folder 'overrides' within the 'lang' folder.

2. Create or copy the language file you want to override.
   For example, to create an override for the English language you create
   ./lang/overrides/english.php

3. Open the newly created file in your preferred editor.

4. Start the file with a PHP tag '<?php' indicating PHP code is to be used.

5. Enter the variable(s) you wish to override.
   For example, if you wanted to change "Welcome to our members area" you
   would locate the proper variable within ./lang/english.php and place it
   into the overrides english file with your preferred change:

    ./lang/english.php
      $_LANG['headertext'] = "Welcome to our members area.";

    ./lang/overrides/english.php
      $_LANG['headertext'] = "Welcome home!";

6. For each variable you wish you change, repeat step #5.
   For example, a completed overrides file should look something like this:

    ./lang/overrides/english.php
      <?php
      $_LANG['headertext'] = "Welcome home!";
      $_LANG['addtocart'] = "Add to Basket";
      $_LANG['cartproductaddons'] = "Product Extras";

7. Save, and you're done!

For further help, please visit http://docs.whmcs.com/Language_Overrides

===============================================================================

Thank you for choosing WHMCS

WHMCompleteSolution
www.whmcs.com