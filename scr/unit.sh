#!/bin/bash
mysql -tu root intersango < sanity.sql
echo -en 'Bitcoin balance: ' && bitcoind getbalance ""
php summa.php
