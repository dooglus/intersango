import MySQLdb
import gtk
import decimal

TWOPLACES = decimal.Decimal(10) ** -2

def copy_to_clipboard(text):
    clipboard = gtk.clipboard_get()
    clipboard.set_text(text)
    clipboard.store()

def cursor():
    db_handle = MySQLdb.connect("localhost", "root", "", "intersango")
    return db_handle.cursor()

def quantize(amount):
    return amount.quantize(TWOPLACES)

def get_withdrawals(curs, status):
    curs.execute("""
        SELECT 
            requests.reqid AS reqid,
            amount/100000000 AS amount,
            name,
            bank,
            acc_num,
            sort_code
        FROM
            requests
        JOIN
            uk_requests
        ON
            uk_requests.reqid=requests.reqid
        WHERE
            req_type='WITHDR'
            AND status='%s'
            AND curr_type='GBP'
        """%status)
    return curs.fetchall()
