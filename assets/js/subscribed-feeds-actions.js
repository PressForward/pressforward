jQuery(window).load(function() {
  pf_switch_status_look();
  //alert('Loaded');
  jQuery('body.post-type-pf_feed').on('click', '.edit-post-status', function(){
    console.log('click edit status');
    pf_switch_status_look();
  });

});

function pf_switch_status_look(){
  jQuery('body.post-type-pf_feed #submitdiv h3.hndle span').text('Activate');
  jQuery('body.post-type-pf_feed').find('#post_status option[value="draft"]').text('Inactive');
  jQuery('body.post-type-pf_feed').find('#post_status option[value="publish"]').text('Active');
  var status = jQuery.trim(jQuery('body.post-type-pf_feed').find('#post-status-display').text().replace('/ /g',''));
  //console.log(status);
  if ('Published' == status) {
    jQuery('body.post-type-pf_feed').find('#post-status-display').text('Active');
  }
  if ('Draft' == status) {
    jQuery('body.post-type-pf_feed').find('#post-status-display').text('Inactive');
  }

  jQuery('body.post-type-pf_feed').find('#save-post').attr('value', 'Save Inactive');

  var button = jQuery('body.post-type-pf_feed').find('#publish').attr('value');
  if ('Publish' == button){
    var button = jQuery('body.post-type-pf_feed').find('#publish').attr('value', 'Make Active');
  }

}
