import withdraw_helper
import decimal

cursor = withdraw_helper.cursor()
withdrawals = withdraw_helper.get_withdrawals(cursor, 'AWAIT')

total = decimal.Decimal(0)
count = 1
for reqid, amount, name, bank, acc_num, sort_code in withdrawals:
    print count, '/', len(withdrawals)
    print
    count += 1

    # truncate decimals to 2 places
    amount = withdraw_helper.quantize(amount)
    total += amount

    print 'Search for: \t', acc_num[0:4], acc_num[4:]
    print
    print 'Name =\t\t', name

    if len(sort_code) != 6:
        print 'Sort code is wrong length'
        print 'Request ID =\t', reqid
        break
    sort_code = sort_code[0:2], sort_code[2:4], sort_code[4:6]

    print 'Sort code =\t', sort_code[0], sort_code[1], sort_code[2]
    print 'Amount =\t', amount

    if len(acc_num) != 8:
        print 'Account number is not 8 digits'
        print acc_num
        print 'Request ID =\t', reqid
        break

    form_info = "javascript:function f(){"
    form_info += "document.forms[0]['frmMakePayment:amount'].value='%s';"%amount
    form_info += "}f();\n";
    withdraw_helper.copy_to_clipboard(form_info)

    print
    print 'Add payment.'
    raw_input()

    cursor.execute("""
        UPDATE requests
        SET status='DONE'
        WHERE
            reqid='%s'
            AND status='AWAIT'
    """, (reqid,))
    print "-----------------------------------"

print 'TOTAL SHOULD BE:'
print '***********', total, '***********'

