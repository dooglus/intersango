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

    if (!typed_price && currency in exchange_rates)
        $('#price').attr('value', exchange_rates[currency]);
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
    $('#outamount').attr('value', '');
}
function select_currency_out(caller)
{
    $('#currsel_out').css('visibility', 'hidden');
    curr = jQuery('.currcode', caller).text();
    curr = curr.toLowerCase();
    set_currency_out(curr);
    set_currency_in(twin_currency(curr));
    $('#outamount').attr('value', '');
    $('#inamount').attr('value', '');
}

function typed_amount(this_name, change_name)
{
    this_obj = $('#' + this_name + 'amount');
    change_obj = $('#' + change_name + 'amount');
    price_obj = $('#price');

    price_text = price_obj.attr('value');
    this_text = this_obj.attr('value');

    if (!price_text || !this_text)
        return;

    price = parseFloat(price_text);
    this_amount = parseFloat(this_text);

    if (      price <= 0 || !isFinite(      price) || isNaN(      price) ||
        this_amount <= 0 || !isFinite(this_amount) || isNaN(this_amount)) {
        change_obj.attr('value', '');
        return;
    }

    a_curr = jQuery('.currcode', '#incurrency').text();
    if ((this_name == 'out' && a_curr == 'btc') ||
        (this_name == 'in'  && a_curr != 'btc'))
        price = 1.0/price;

    val = this_amount * price;

    // toFixed(2) rounds 0.235001 up to 0.24, meaning the order doesn't quite match
    // add on / take off 0.0049999 to make sure of a match
    if (this_name == 'out')
        val += 0.000049999;
    else
        val -= 0.000049999;

    val = val.toFixed(4)
    val = val.replace(/([.].*?)0+$/, '$1'); // remove trailing zeroes after the decimal point
    val = val.replace(/[.]$/, '');          // remove trailing decimal point
    change_obj.attr('value', val);
}

function is_typing(e)
{
    code = e.keyCode ? e.keyCode : e.charCode;
    return (code == 8 || code > 31);
}

function typed_amount_in(e)
{
    if (!is_typing(e)) return;
    typed_amount('in', 'out');
}

function typed_amount_out(e)
{
    if (!is_typing(e)) return;
    typed_amount('out', 'in');
}

function typed_amount_price(e)
{
    if (!is_typing(e)) return;
    typed_price = true;
    typed_amount('in', 'out');
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

