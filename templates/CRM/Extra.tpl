{literal}
<script type="text/javascript">
cj(document).ready( function() {
var priceset = '{/literal} {$foursome.field} {literal}';
var value = {/literal} {$foursome.value} {literal};
var profileId = {/literal} {$playerProfileID} {literal};
  
  cj('.event_fee_s_-content input').click(function(){
    if (this.value != value) {
      cj('.crm-profile-id-' + profileId).hide();
    }
    else {
      cj('.crm-profile-id-' + profileId).show();    
    }
  });
});

</script>
{/literal}