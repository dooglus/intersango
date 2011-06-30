import withdraw_helper

def field_name_1(field, ben='Ben'):
    field = 'pnlSetupNewPayeePayaPerson:frmPayaPersonArrangement:str' + ben + field
    return "document.forms[1]['" + field + "'].value='%s';"

cursor = withdraw_helper.cursor()
withdrawals = withdraw_helper.get_withdrawals(cursor, 'PROCES')

count = 1
for reqid, amount, name, bank, acc_num, sort_code in withdrawals:
    print count, '/', len(withdrawals)
    print
    count += 1

    # truncate decimals to 2 places
    amount = withdraw_helper.quantize(amount)

    print 'Name =\t\t', name
    print 'Account =\t', acc_num[0:4], acc_num[4:]

    if len(sort_code) != 6:
        print 'Sort code is wrong length'
        print 'Request ID =\t', reqid
        break
    sort_code = sort_code[0:2], sort_code[2:4], sort_code[4:6]

    print 'Sort code =\t', sort_code[0], sort_code[1], sort_code[2]
    print
    print 'Amount =\t', amount

    if len(acc_num) != 8:
        print 'Account number is not 8 digits'
        print acc_num
        print 'Request ID =\t', reqid
        break

    form_info = "javascript:function f(){"
    form_info += field_name_1('Name')%name
    form_info += field_name_1('SortCode')%sort_code[0]
    form_info += field_name_1('SortCode_p2')%sort_code[1]
    form_info += field_name_1('SortCode_p3')%sort_code[2]
    form_info += field_name_1('AccountNumber')%acc_num
    form_info += field_name_1('Reference', '')%'Britcoin'
    form_info += "}f();\n";
    withdraw_helper.copy_to_clipboard(form_info)

    print
    print 'Add person.'
    raw_input()

    cursor.execute("""
        UPDATE requests
        SET status='AWAIT'
        WHERE
            reqid='%s'
            AND status='PROCES'
    """, (reqid,))
    print "-----------------------------------"

