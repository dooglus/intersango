function set_curr_block(elem, currency, textname)
{
    elem.children('.currflag').attr('src', 'images/'.concat(currency).concat('_flag.png'));
    elem.children('.currname').text(textname);
    elem.children('.currbox_right').children('.currcode').text(currency);
}
function set_currency(elem, currency)
{
    switch(currency)
    {
        case 'aud':
            set_curr_block(elem, 'aud', 'Australian Dollar');
        break;

        case 'btc':
            set_curr_block(elem, 'btc', 'Bitcoin');
        break;
    }
}
function twin_currency(currency)
{
    switch(currency)
    {
        case 'btc':
            return 'aud';
        case 'aud':
        default:
            return 'btc';
    }
}

function set_currency_in(currency)
{
    ic = $('#incurrency');
    set_currency(ic, currency);
}
function set_currency_out(currency)
{
    ic = $('#outcurrency');
    set_currency(ic, currency);
}

function rolldown(cs, ic)
{
    if (cs.css('visibility') == 'hidden') {
        cs.position({
            my: "left top",
            at: "left bottom",
            of: ic
        });
        cs.css('visibility', 'visible');
    }
    else
        cs.css('visibility', 'hidden');
}
function hide_rolldown(cs)
{
    cs.css('visibility', 'hidden');
}
function rolldown_in()
{
    rolldown($('#currsel_in'), '#incurrency');
    hide_rolldown($('#currsel_out'));
}
function rolldown_out()
{
    rolldown($('#currsel_out'), '#outcurrency');
    hide_rolldown($('#currsel_in'));
}

function select_currency_in(caller)
{
    $('#currsel_in').css('visibility', 'hidden');
    curr = jQuery('.currcode', caller).text();
    curr = curr.toLowerCase();
    set_currency_in(curr);
    set_currency_out(twin_currency(curr), false);
    $('#inamount').attr('value', '');
    typed_amount_in();
}
function select_currency_out(caller)
{
    $('#currsel_out').css('visibility', 'hidden');
    curr = jQuery('.currcode', caller).text();
    curr = curr.toLowerCase();
    set_currency_out(curr);
    set_currency_in(twin_currency(curr));
    $('#outamount').attr('value', '');
    typed_amount_out();
}

function typed_amount(this_name, change_name)
{
    this_obj = $('#' + this_name + 'amount');
    change_obj = $('#' + change_name + 'amount');
    a_obj = $('#inamount');
    b_obj = $('#outamount');
    a_curr = jQuery('.currcode', '#incurrency').text();
    b_curr = jQuery('.currcode', '#outcurrency').text();
    if (a_curr in exchange_rates) {
        a_curr_rates = exchange_rates[a_curr];
        if (b_curr in a_curr_rates) {
            text_field = this_obj.attr('value');
            if (text_field == '') {
                change_obj.attr('value', 0);
                return;
            }
            else if (isNaN(text_field)) {
                change_obj.attr('value', '-');
                return;
            }
            rate = a_curr_rates[b_curr];
            val = parseFloat(text_field);
            val *= rate;
            change_obj.attr('value', val.toFixed(2));
        }
        else {
            b_obj.attr('value', 'N/A');
        }
    }
    else {
        b_obj.attr('value', 'N/A');
    }
}
function typed_amount_in()
{
    typed_amount('in', 'out');
}
function typed_amount_out()
{
    //typed_amount('out', 'in');
}

function buy_clicked()
{
    curr_type = jQuery('.currcode', '#incurrency').text();
    want_curr_type = jQuery('.currcode', '#outcurrency').text();
    amount = $('#inamount').attr('value');
    want_amount = $('#outamount').attr('value');
    $("input[name='type']").val(curr_type);
    $("input[name='amount']").val(amount);
    if (amount == '' || want_amount == '' || isNaN(amount) || isNaN(want_amount)) {
        alert("Invalid amount specified.");
        return false;
    }
    $("input[name='want_type']").val(want_curr_type);
    $("input[name='want_amount']").val(want_amount);
    return true;
}

