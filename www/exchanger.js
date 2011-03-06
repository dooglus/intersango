var default_currency = "usd";

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
    case 'usd':
        set_curr_block(elem, 'usd', 'US Dollar');
    break;

    case 'eur':
        set_curr_block(elem, 'eur', 'Euro');
    break;

    case 'gbp':
        set_curr_block(elem, 'gbp', 'British Pound');
    break;

    case 'btc':
        set_curr_block(elem, 'btc', 'Bitcoin');
    break;
    }
}
function set_currency_in(currency)
{
    ic = $('#incurrency');
    set_currency(ic, currency.toLowerCase());
}
function set_currency_out(currency)
{
    ic = $('#outcurrency');
    set_currency(ic, currency.toLowerCase());
}

function rolldown(cs, ic)
{
    if (cs.css('visibility') == 'hidden') {
        cs.css('visibility', 'visible');
        offset = ic.offset();
        cs.css('top', offset.top + 26);
        cs.css('left', offset.left);
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
    rolldown($('#currsel_in'), $('#incurrency'));
    hide_rolldown($('#currsel_out'));
}
function rolldown_out()
{
    rolldown($('#currsel_out'), $('#outcurrency'));
    hide_rolldown($('#currsel_in'));
}
function select_currency_in(caller)
{
    curr = jQuery('.currcode', caller).text();
    set_currency_in(curr);
    $('#currsel_in').css('visibility', 'hidden');
}
function select_currency_out(caller)
{
    curr = jQuery('.currcode', caller).text();
    set_currency_out(curr);
    $('#currsel_out').css('visibility', 'hidden');
}

function typed_amount(a, b)
{
    text_field = a.attr('value');
    if (text_field == '') {
        b.attr('value', 0);
        return;
    }
    else if (isNaN(text_field)) {
        b.attr('value', '-');
        return;
    }
    val = parseFloat(text_field);
    val *= 2.5;
    b.attr('value', val);
}
function typed_amount_in()
{
    typed_amount($('#inamount'), $('#outamount'));
}
function typed_amount_out()
{
    typed_amount($('#outamount'), $('#inamount'));
}


