# command history to setup Intersango on Amazon Linux AMI
# some command responses are prefixed with “##”

### get the current version of this script by running:
# GET https://raw.github.com/dooglus/intersango/pp/setup-intersango.sh > intersango.sh

########################################################################
# passwords
# bitcoind username
BITCOIN_PW='frank45$lin'
# mysql root password
MYSQL_ROOT_PW='benjamin22%'
# password to connect Intersango database
MYSQL_INTERSANGO_PW='benjamin22%'
# passphrase to encrypt wallet backups
BITCOIN_WALLET_BACKUP_PP="iZ-QTlng ungue5bl3 p@ssphr.se fUr wa1'utbkup"
# duo setup - to register, visit http://www.duosecurity.com/pricing
# then fill in form on right, follow the signup process, verify account,
# and create a new itegration of type 'Web SDK'
DUO_SECRET_STRING='sdfieruhy8yiuhgifhuvhw8gyg92fy3287tjbhpoergio'
DUO_INTEGRATION_KEY='DIB1AA2XQZ52AWNRZK1U'
DUO_SECRET_KEY='WMHwNR52u4suB2UC8cXc4ooPbJpa689F0775YCqU'
DUO_API_HOSTNAME='api-afdc7efd.duosecurity.com'

# other parameters
# bitcoind username
BITCOIN_USER='benjamin33!'
# username to connect Intersango database
MYSQL_INTERSANGO_USER='intersango'
# Intersango database's name
MYSQL_INTERSANGO_DBNAME='intersango'
# Linux username for Intersango user
SYSTEM_INTERSANGO_USER='intersango'
# server URL without http://
SITE_HOST_NAME='ec2-54-247-132-226.eu-west-1.compute.amazonaws.com'
########################################################################

# install Apache
echo Installing Apache ...
yum -y install httpd
##Complete!


# enable Apache start on boot
echo Enabling Apache start on boot ...
chkconfig httpd on


# start Apache
echo Starting Apache ...
service httpd start
##Starting httpd:                                            [  OK  ]


# install PHP
echo Installing PHP and modules ...
yum -y install php php-mysql php-bcmath
##Complete!


# install git
echo Installing git ...
yum -y install git
##Complete!


# create intersango user
adduser $SYSTEM_INTERSANGO_USER
chmod 701 /home/$SYSTEM_INTERSANGO_USER
passwd --lock $SYSTEM_INTERSANGO_USER
##passwd: Success


# set up .bashrc, .gitconfig, etc.
su - $SYSTEM_INTERSANGO_USER <<EOF

cat >> .bashrc <<EOF2
alias cp='cp -i'
alias mv='mv -i'
alias rm='rm -i'
alias bc='~/bin/bitcoind'
alias i='cd ~/intersango'
alias l='ls -l'
alias la='ls -al'
alias lt='ls -altr | tail -20'
alias pull='pushd ~/intersango; git stash && git pull && git stash apply; popd'
alias sa='. ~/.bashrc'
alias sql='mysql -u $MYSQL_INTERSANGO_USER --password="$MYSQL_INTERSANGO_PW" $MYSQL_INTERSANGO_DBNAME'
EOF2

cat > .gitconfig <<EOF2
[core]
	pager = cat
[alias]
	co = checkout
	br = branch
	ci = commit
	tree = log --date=local --pretty='format:%h  %ad  %s' --decorate --graph
[user]
	email = no@email.com
	name = intersango
EOF2
EOF


# clone Intersango code
su - $SYSTEM_INTERSANGO_USER <<EOF
echo Cloning Intersango code ...
git clone git://github.com/dooglus/intersango.git
cd intersango
git checkout pp
EOF
##Resolving deltas: 100% (2953/2953), done.


# install Mysql
echo Installing Mysql ...
yum -y install mysql-server
##Complete!


# enable Mysql start on boot
echo Enabling Mysql start on boot ...
chkconfig mysqld on


# start Mysql
echo Starting Mysql ...
service mysqld start
##Starting mysqld:                                           [  OK  ]


# secure Mysql
echo Securing Mysql ...
/usr/bin/mysql_secure_installation <<COMMANDS

Y
$MYSQL_ROOT_PW
$MYSQL_ROOT_PW
Y
Y
Y
Y
COMMANDS



# set up timezones in MySQL
echo Setting up MySQL timezones ...
mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u root mysql --password="$MYSQL_ROOT_PW"
## Warning: Unable to load '/usr/share/zoneinfo/zone.tab' as time zone. Skipping it.



# create Intersango database in Mysql
echo Creating Intersango database ...
mysql -u root mysql --password=$MYSQL_ROOT_PW <<COMMANDS
create database $MYSQL_INTERSANGO_DBNAME;
create user $MYSQL_INTERSANGO_USER identified by '$MYSQL_INTERSANGO_PW';
grant all privileges on $MYSQL_INTERSANGO_DBNAME.* to $MYSQL_INTERSANGO_USER with grant option;
quit
COMMANDS


# set up database schema in Intersango Mysql database
echo Setting up Intersango database schema ...
mysql -u $MYSQL_INTERSANGO_USER --password="$MYSQL_INTERSANGO_PW" $MYSQL_INTERSANGO_DBNAME < ~intersango/intersango/DATABASE


# download and symlink to bitcoin executable
echo Downloading bitcoind executable
su - $SYSTEM_INTERSANGO_USER <<EOF
wget -O- http://voxel.dl.sourceforge.net/project/bitcoin/Bitcoin/bitcoin-0.6.0/bitcoin-0.6.0-linux.tar.gz | tar xfz -
mkdir bin
ln -s ../bitcoin-0.6.0-linux/bin/32/bitcoind bin/bitcoind
EOF
##2012-03-08 21:11:48 (6.43 MB/s) - written to stdout [9903046/9903046]


# configure bitcoind
echo Configuring bitcoind
su - $SYSTEM_INTERSANGO_USER <<EOF
mkdir -p .bitcoin
echo "$BITCOIN_WALLET_BACKUP_PP" > .bitcoin/pp
cat > ~/.bitcoin/bitcoin.conf <<EOF2
rpcuser=$BITCOIN_USER
rpcpassword=$BITCOIN_PW
EOF2
EOF


# configure Intersango
echo Configuring Intersango
su - $SYSTEM_INTERSANGO_USER <<EOF
touch intersango/log{,-{problem,error,bad-page}}.txt
chmod o+w intersango/{docs,log{,-{problem,error,bad-page}}.txt,locks}
cat > db.intersango.sh <<EOF2
MYSQL_INTERSANGO_USER='$MYSQL_INTERSANGO_USER'
MYSQL_INTERSANGO_PW='$MYSQL_INTERSANGO_PW'
MYSQL_INTERSANGO_DBNAME='$MYSQL_INTERSANGO_DBNAME'
USER='$SYSTEM_INTERSANGO_USER'
EOF2
cat > db.intersango.inc <<EOF2
<?php
mysql_connect('localhost', '$MYSQL_INTERSANGO_USER', '$MYSQL_INTERSANGO_PW') or die(mysql_error());
mysql_select_db('$MYSQL_INTERSANGO_DBNAME') or die(mysql_error());
function connect_bitcoin() {
    disable_errors_if_not_me();
    \\\$bitcoin = new jsonRPCClient('http://$BITCOIN_USER:$BITCOIN_PW@127.0.0.1:8332/');
    enable_errors();
    return \\\$bitcoin;
}
?>
EOF2
EOF



# configure apache
rmdir /var/www/html/
ln -s /home/$SYSTEM_INTERSANGO_USER/intersango/htdocs /var/www/html



# configure Intersango
echo Configuring Intersango ...
configure_php_field() {
    field=$1
    file=$2
    value=$3
    sed -i -e "s|^define('$field'.*|define('$field', $value);|" /home/$SYSTEM_INTERSANGO_USER/intersango/$file
}
configure_sh_field() {
    field=$1
    file=$2
    value=$3
    sed -i -e "s|^$field=.*|$field=$value|" /home/$SYSTEM_INTERSANGO_USER/intersango/$file
}
configure_php_field SITE_URL              htdocs/config.php	        "'https://$SITE_HOST_NAME/'"
configure_php_field AKEY                  duo_config.php	        "'$DUO_SECRET_STRING'"
configure_php_field IKEY                  duo_config.php	        "'$DUO_INTEGRATION_KEY'"
configure_php_field SKEY                  duo_config.php	        "'$DUO_SECRET_KEY'"
configure_php_field HOST                  duo_config.php	        "'$DUO_API_HOSTNAME'"
# configure_sh_field USER                 bin/every-minute	        "'$SYSTEM_INTERSANGO_USER'"



# set up crontab
echo Installing crontab
su - $SYSTEM_INTERSANGO_USER <<EOF
crontab intersango/crontab.txt
EOF



# restart Apache
echo Restarting Apache ...
service httpd restart
##Starting httpd:                                            [  OK  ]


# still to do:

# configure the other configurable stuff - there's lots of WBX-specific stuff there
# edit html where needed - there are WBX references
# put a cron job in place to:
#   run scripts that need running
#   (re)start bitcoind if it's not running
