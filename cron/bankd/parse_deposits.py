import MySQLdb
import decimal

import re

db_password = ""

db = MySQLdb.connect("localhost","intersango",db_password,"intersango")

c = db.cursor()
c.execute("SELECT bid,entry FROM bank_statement WHERE status='VERIFY' and reqid IS NULL")
for bid,entry in c:
    try:
        line = entry.split(",")
        if line[6] == '':
            print("PAYOUT",bid,entry)
            c.execute("UPDATE bank_statement SET status='PAYOUT' WHERE bid=%s",(bid,))
        if line[5] == '':
            matches = re.findall("[A-Z0-9]{8}",line[4])
            if len(matches) >= 1:
                good_reference = False
                for match in matches:
                    deposit_reference = match.strip('., \t\r\n"')
                    amount = int(decimal.Decimal(line[6]) * ( 10 ** 8))
                    c.execute('SELECT uid FROM users WHERE deposref=%s',(deposit_reference,))
                    result = c.fetchone()
                    if result:
                        uid = result[0]
                        
                        c.execute("""
                        UPDATE
                            bank_statement
                        SET
                            status='PROC'
                        WHERE
                            bid=%s""",(bid,))
                            
                        c.execute("""
                        INSERT INTO requests (
                            req_type,
                            curr_type,
                            uid,
                            amount
                        )
                        VALUES
                        (
                            'DEPOS',
                            'GBP',
                            %s,
                            %s
                        )""",(uid,amount))
                        
                        reqid = c.lastrowid
                        
                        c.execute("""
                        UPDATE
                            bank_statement
                        SET
                            reqid=%s,
                            status='FINAL'
                        WHERE
                            bid=%s""",(reqid,bid))
                            
                        print("DEPOS",bid,uid,amount,reqid,entry)
                        
                        good_reference = True
                        break
                        
                if not good_reference:
                    print("BADREF",entry)
                    c.execute("UPDATE bank_statement SET status='BADREF' WHERE bid=%s",(bid,))
    except StopIteration:
        pass

