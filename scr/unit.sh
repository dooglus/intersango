#!/bin/bash
cat match_statements.sql sanity.sql | mysql -tu root intersango 
echo -en 'Bitcoin balance: ' && bitcoind getbalance ""
php summa.php
