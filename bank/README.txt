The basic command line steps to generate a private and public key using OpenSSL are as follows:

# Step 1 – generates your private key
openssl genrsa -out privatekey.pem 1024

# Step 2 – generates your public key which you use when registering your private application
openssl req -newkey rsa:1024 -x509 -key privatekey.pem -out publickey.cer -days 365

# Step 3 – exports your public and private key to a pfx file which can be used to sign your OAuth messages.
openssl pkcs12 -export -out public_privatekey.pfx -inkey privatekey.pem -in publickey.cer

is step 3 needed?
