#!/bin/bash
mysql -tu root intersango < sanity.sql
bitcoind getbalance ""
php summa.php
