{literal}
<script type="text/javascript">
cj(document).ready( function() {
cj('.post-meta').hide();
{/literal} {if $playerProfileID} {literal}
var priceset = '{/literal} {$foursome.field} {literal}';
var value = {/literal} {$foursome.value} {literal};
var profileId = {/literal} {$playerProfileID} {literal};
  if (cj('input[name=' + priceset +']:checked').val() != value) {
    cj('.crm-profile-id-' + profileId).hide();
  }
  else {
    cj('.crm-profile-id-' + profileId).show();    
  }

  cj('.event_fee_s_-content input').click(function(){
    if (this.value != value) {
      cj('.crm-profile-id-' + profileId).hide();
    }
    else {
      cj('.crm-profile-id-' + profileId).show();    
    }
  });
{/literal}{else}{literal}
  cj('.event_info-group div.location').removeClass('vcard');
  cj('.event_address-section div.location').removeClass('vcard');
{/literal}{/if}
{if $otherOption}{literal}
  var a = 'price_{/literal}{$otherOption.0}{literal}';
  var b = 'price_{/literal}{$otherOption.1}{literal}';
{/literal}{/if}{literal}

  if (cj('input[name=' + a +']:checked').val() == 0) {
    cj('input[name=' + b +']').parent().parent().show();
  }
  else {
    cj('input[name=' + b +']').parent().parent().hide();
    cj('input[name=' + b +']').val('');	
  }
  cj('input[name=' + a +']').click(function(){
    if (this.value == 0) {
      cj('input[name=' + b +']').parent().parent().show();
    }
    else {
      cj('input[name=' + b +']').parent().parent().hide();
      cj('input[name=' + b +']').val('');
      cj('input[name=' + b +']').trigger('blur');
    }
  });
});
</script>
{/literal}