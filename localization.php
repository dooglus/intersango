<?php

$locale = LOCALE;

if (isSet($_GET["locale"])) $locale = get("locale", '_');
putenv("LC_ALL=$locale");
setlocale(LC_ALL, $locale);
bindtextdomain("messages", ABSPATH . "/locale");
textdomain("messages");

// echo "locale: $locale<br/>\n";
// echo _("Hello World!") . "\n";

?>
