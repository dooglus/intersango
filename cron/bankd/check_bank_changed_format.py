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
        if b2[0] in fin_bids or b2[3] is None or b1[3] is None:
            continue
        count += 1
        print 'Found -------------------'
        print b1
        print '####'
        print b2
        reqid1 = b1[3]
        reqid2 = b2[3]
        c.execute("""
            SELECT *
            FROM requests
            WHERE reqid IN (%i, %i)
            """%(reqid1, reqid2))
        reqs = c.fetchall()
        print
        uid = None
        for r in reqs:
            if uid is None:
                uid = r[2]
            elif uid != r[2]:
                print 'IGNOREEEEE******************************'
            print r
        c.execute("""
            SELECT *
            FROM purses
            WHERE
                uid=%i
                AND type='GBP'
            """%uid)
        print c.fetchall()
        print '-------------------------'

print 'Total:', count
