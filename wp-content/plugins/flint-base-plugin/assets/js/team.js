(function($){
  function runTeamFilter(){
    const $form = $('#teamSearch');
    if (!$form.length || typeof flintTeam === 'undefined') return;
    $('#datafetch').addClass('is-loading');
    $.post(flintTeam.ajaxUrl, {
      action: 'flint_team_filter', nonce: flintTeam.nonce,
      category: $('#categoryTerm').val() || '', location: $('#locationTerm').val() || ''
    }).done(function(response){ if(response && response.success){ $('#datafetch').html(response.data.html); } })
      .always(function(){ $('#datafetch').removeClass('is-loading'); });
  }
  $(document).on('submit','#teamSearch',function(e){ e.preventDefault(); runTeamFilter(); });
  $(document).on('change','#teamSearch select',function(){ $('#teamSearch').trigger('submit'); });
})(jQuery);
