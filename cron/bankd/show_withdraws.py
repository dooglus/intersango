import MySQLdb
import gtk
import decimal

TWOPLACES = decimal.Decimal(10) ** -2

def copy_to_clipboard(text):
    clipboard = gtk.clipboard_get()
    clipboard.set_text(text)
    clipboard.store()

db_handle = MySQLdb.connect("localhost", "root", "", "intersango")
cursor = db_handle.cursor()
cursor.execute("""
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
        AND status='PROCES'
        AND curr_type='GBP'
    """)
withdrawals = cursor.fetchall()

count = 1
for reqid, amount, name, bank, acc_num, sort_code in withdrawals:
    print count, '/', len(withdrawals)
    print
    count += 1

    # truncate decimals to 2 places
    amount = amount.quantize(TWOPLACES)

    print 'Name =\t\t', name
    print 'Reference =\t', acc_num[0:4], acc_num[4:]
    print 'Amount =\t', amount

    if len(sort_code) != 6:
        print 'Sort code is wrong length'
        print 'Request ID =\t', reqid
        break
    sort_code = sort_code[0:2], sort_code[2:4], sort_code[4:6]

    form_info = "javascript:function f(){"
    form_info += "document.forms[0]['Beneficiary'].value='%s';"%(name,)
    form_info += "document.forms[0]['SortCode1'].value='%s';"%(sort_code[0],)
    form_info += "document.forms[0]['SortCode2'].value='%s';"%(sort_code[1],)
    form_info += "document.forms[0]['SortCode3'].value='%s';"%(sort_code[2],)
    form_info += "document.forms[0]['AccountNumber'].value='%s';"%(acc_num,)
    form_info += "document.forms[0]['Reference'].value='Britcoin';"
    form_info += "}f();\n";
    copy_to_clipboard(form_info)

    print
    print 'First page.'
    raw_input()

    amount = str(amount).split('.')
    amount_whole = amount[0]
    amount_decimal = amount[1]
    form_info = "javascript:function f(){"
    form_info += "document.forms[0]['AmountWhole'].value='%s';"%(amount_whole,)
    form_info += "document.forms[0]['AmountDecimal'].value='%s';"%(amount_decimal,)
    form_info += "}f();\n"
    copy_to_clipboard(form_info)

    print 'Second page.'
    raw_input()
    print "-----------------------------------"

