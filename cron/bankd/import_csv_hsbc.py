import MySQLdb
import hashlib
import sys

def database_handle():
    return MySQLdb.connect('localhost', 'root', '', 'intersango')

def show_help():
    print 'python import_csv_hsbc.py [FILENAME]'

def import_lines(lines):
    handle = database_handle()
    cursor = handle.cursor()
    print
    for line in lines:
        print 'Importing:', line
        cursor.execute("""
            INSERT INTO
                bank_statement (bank_name, entry)
            VALUES (
                'HSBC',
                '%s'
            )
            """%line)

def read_file(filename):
    handle = open(filename)
    text = handle.read()
    lines = text.split('\n')
    # HSBC orders files backwards from newest to oldest by default
    # re-order correctly the file
    lines.reverse()
    # remove empty element
    if lines[0] == '':
        lines = lines[1:]
    return lines

def read_database(num_lines):
    handle = database_handle()
    cursor = handle.cursor()
    # select last num_lines entries from bank_statement
    # we do that using a sub-query that orders desc, selects first X lines
    # then re-orders it asc
    cursor.execute("""
        SELECT entry
        FROM (
            SELECT 
                bid, entry
            FROM 
                bank_statement
            WHERE 
                bank_name='HSBC'
            ORDER BY 
                bid DESC
            LIMIT 
                %i
            ) AS b
        ORDER BY bid ASC
        """%num_lines)
    return [c for c, in cursor.fetchall()]

def run_parser():
    if len(sys.argv) != 2:
        show_help()
        return -1
    csv_lines = read_file(sys.argv[1])
    db_lines = read_database(len(csv_lines))
    print db_lines
    print
    print csv_lines

    hash_pair = lambda line: (hashlib.sha512(line).digest(), line)
    make_hash_pairs = lambda lines: [hash_pair(l) for l in lines]
    csv_pairs = make_hash_pairs(csv_lines)
    db_pairs = make_hash_pairs(db_lines)

    while len(db_pairs) > 0 and csv_pairs[0][0] != db_pairs[0][0]:
        print 'Dropping:', db_pairs.pop(0)[1]

    if len(db_pairs) == 0:
        # No matching lines
        import_lines(csv_lines)
        return 0

    while len(db_pairs) > 0:
        # Make sure that at least first hashe from both sets of lines match
        assert(csv_pairs[0][0] == db_pairs[0][0])
        print 'Deleting:', csv_pairs.pop(0)[1]
        db_pairs.pop(0)

    remaining_lines = [line[1] for line in csv_pairs]
    print 'Remaining lines:', remaining_lines
    import_lines(remaining_lines)

if __name__ == '__main__':
    sys.exit(run_parser())

