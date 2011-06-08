import MySQLdb
import getpass

db = MySQLdb.connect("localhost","root",getpass.getpass(),"intersango")

expected_site_balance = {}
expected_site_balance['GBP'] = 0
expected_site_balance['BTC'] = 0

site_balance = {}
site_balance['GBP'] = 0
site_balance['BTC'] = 0

def balance_user(user_id):
    c = db.cursor()
    c.execute("SELECT amount,type FROM purses WHERE uid=%s",(user_id,))
    balances = c.fetchall()
    
    c.execute("SELECT amount,curr_type FROM requests WHERE req_type='DEPOS' AND status='FINAL' AND uid=%s",(user_id,))
    deposits = c.fetchall()

    c.execute("SELECT amount,curr_type FROM requests WHERE req_type='WITHDR' AND status='FINAL' AND uid=%s",(user_id,))
    withdrawals = c.fetchall()
    
    c.execute("SELECT amount,curr_type FROM requests WHERE req_type='WITHDR' AND (status='VERIFY' OR status='PROCES') AND uid=%s",(user_id,))
    pending_withdrawals = c.fetchall()

    c.execute("SELECT orderbook.orderid,orderbook.type,orderbook.want_type,transactions.a_amount AS amount ,transactions.b_amount AS want_amount FROM transactions JOIN orderbook ON orderbook.orderid=transactions.a_orderid WHERE transactions.b_amount=-1 AND orderbook.uid=%s",(user_id,))
    cancelled_transactions = c.fetchall()

    #select orders twice to get orders in reverse direction
    c.execute("SELECT orderbook.type,orderbook.want_type,transactions.a_amount AS amount ,transactions.b_amount AS want_amount FROM transactions JOIN orderbook ON orderbook.orderid=transactions.a_orderid WHERE transactions.b_amount!=-1 AND orderbook.uid=%s",(user_id,))
    transactions = c.fetchall()
    c.execute("SELECT orderbook.type,orderbook.want_type,transactions.b_amount AS amount ,transactions.a_amount AS want_amount FROM transactions JOIN orderbook ON orderbook.orderid=transactions.b_orderid WHERE transactions.b_amount!=-1 AND orderbook.uid=%s",(user_id,))
    transactions += c.fetchall()

    c.execute("SELECT amount,type FROM orderbook WHERE status='OPEN' AND uid=%s",(user_id,))
    orders = c.fetchall()
    c.close()
    
    expected_balance = {}
    expected_balance['GBP'] = 0
    expected_balance['BTC'] = 0
    
    for amount,type in deposits:
        expected_balance[type] += amount
        
    for type,want_type,amount,want_amount in transactions:
        expected_balance[type] -= amount
        expected_balance[want_type] += want_amount
        
    for amount,type in orders:
        expected_balance[type] -= amount
    
    for amount,type in withdrawals:
        expected_balance[type] -= amount
        
    for amount,type in pending_withdrawals:
        expected_balance[type] -= amount
        
    out_of_balance = []
    for amount,type in balances:
        site_balance[type] += amount
        expected_site_balance[type] += expected_balance[type]
        if expected_balance[type] != amount:
            out_of_balance.append((user_id,type,expected_balance[type],amount))
    return out_of_balance
    
c = db.cursor()
c.execute("SELECT uid FROM users")

out_of_balance = []

for user in c:
    out_of_balance += balance_user(user[0])

c.close()

if len(out_of_balance) > 0:
    for user_id,type,expected,amount in out_of_balance:
        print(user_id,type,expected,amount)
    
    for type in expected_site_balance.keys():
        print("site_balance",type,expected_site_balance[type],site_balance[type],expected_site_balance[type]-site_balance[type])
