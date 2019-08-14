function renderRec(widget) {
  if (!peeriusDisabled) {
    target = $('peerius-smartrecs-'+widget.position);
    if (target !== null) {
      targetContainer = $('peerius_smartrecs_container');
      //target.hide();
      var peeriusWidgetHeight = 0;
      new Ajax.Updater(target, renderRoute, {
        parameters: {
          recs: JSON.stringify(widget.recs),
          title: target.getAttribute('headline'),
          template: target.getAttribute('template'),
          max: target.getAttribute('max')
        },
        onSuccess: function(e) {
          new Effect.Appear(target, {
            afterFinishInternal: function(e) {
              if (targetContainer != null) {
                if (peeriusWidgetHeight !== targetContainer.getHeight()) {
                  storeWidgetHeight('peerius-smartrecs-'+widget.position, targetContainer.getHeight());
                }
              }
            }
          });
          if (targetContainer != null) {
            peeriusWidgetHeight = targetContainer.getHeight();
          }
        }
      });
      function storeWidgetHeight(id, height) {
        new Ajax.Request('/index.php/peerius/Render/storeHeight', {
          method: 'get',
          parameters: {page: id, height: height}
        });
      }
    }
  }
}

