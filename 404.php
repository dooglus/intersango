<div class='content_box'>
  <h3><?php echo _("Page Not Found"); ?></h3>
  <p>
<?php printf(_("Sorry, but %sthat page%s no longer exists."),
             sprintf('<a href="%s">', getenv("REQUEST_URI")),
             "</a>"); ?>
  </p>
  <p>
  <center><img width='300' src="/images/tumblbeasts/tb_sign1.png" /><br/><small>
<?php printf(_("thanks to %sMatthew Inman%s for the image"),
             '<a target="_blank" href="http://TheOatmeal.com/">',
             "</a>"); ?></small></center>
  </p>
</div>
