#!/bin/bash
mysql -tu root intersango < sanity.sql
php summa.php
