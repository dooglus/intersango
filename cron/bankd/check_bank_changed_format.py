import MySQLdb

db = MySQLdb.connect("localhost", "root", "", "intersango")
c = db.cursor()
c.execute("""
    SELECT *
    FROM bank_statement
    WHERE bank_name='LloydsTSB'
    """)
count = 0
fin_bids = []
for b1 in c.fetchall():
    entry1 = b1[2].split(',')
    bid = b1[0]
    fin_bids.append(bid)
    balance = entry1[-1]
    c.execute("""
        SELECT *
        FROM bank_statement
        WHERE 
            entry LIKE '%%%s'
            AND bank_name='LloydsTSB'
            AND bid!='%i'
            AND status!='PAYOUT'
        """%(balance, bid))
    for b2 in c.fetchall():
        if b2[0] in fin_bids:
            continue
        count += 1
        print 'Found -------------------'
        print b1
        print '####'
        print b2
        print '-------------------------'

print 'Total:', count
