{literal}
<script type="text/javascript">
cj(document).ready( function() {
cj('.post-meta').hide();
{/literal} {if $playerProfileID} {literal}
var priceset = '{/literal} {$foursome.field} {literal}';
var value = {/literal} {$foursome.value} {literal};
var profileId = {/literal} {$playerProfileID} {literal};
  cj('.crm-profile-id-' + profileId + ' .label label' ).each(function(i, j) {
    cj(this).append('<span title="This field is required." class="crm-marker">*</span>');
  });

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
{/literal}{/if}{literal}
});
</script>
{/literal}