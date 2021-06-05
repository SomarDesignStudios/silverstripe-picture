(function($) {
  $.entwine('ss', function($) {
    $('.picture-field').entwine({
      onmatch: function(e) {
        const me = this[0]
        const holder = this.find('.form__fieldgroup.form__field-holder').first()
        const btn = this.find('.btn-remove-cita-picture')

        btn.on('click', e => {
          const list = holder[0].childNodes

          list.forEach((item, i) => {
            if (item.classList) {
              item.remove()
            }
          })

          const p = $('<p />')
          p.css('margin-top', '7px')
          p.html('This picture will be removed upon the page save/publish.<br />If you don\'t wish to remove the picture, just refresh the page now')

          holder.append(input)
          holder.append(p)
        })
      }
    })
  })
}(jQuery))
