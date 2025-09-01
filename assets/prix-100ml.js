jQuery(function ($) {
  var $form = $('.variations_form');
  if (!$form.length) return;

  function extraireMl(txt) {
    if (!txt) return 0;
    var m = txt.match(/([\d\.,]+)\s*ml/i);
    return m ? parseFloat(m[1].replace(',', '.')) : 0;
  }

  function maj(variation) {
    var $cible = $('#prix-100ml');
    if (!$cible.length) return;

    if (!variation) {
      $cible.html(''); // Removed the default message
      return;
    }

    var prix = parseFloat(variation.display_price);
    if (!isFinite(prix) || prix <= 0) {
      $cible.html('<span>' + PRIX100ML.erreur + '</span>');
      return;
    }

    var volume = 0;
    $form.find('select[name^="attribute_"]').each(function () {
      var txt = $(this).find('option:selected').text();
      var v = extraireMl(txt);
      if (v > 0) { volume = v; return false; }
    });

    if (volume > 0) {
      var prix100 = prix * (100 / volume);
      $cible.html('<strong>' + PRIX100ML.label + '</strong> ' + prix100.toFixed(2) + ' ' + PRIX100ML.devise);
    } else {
      $cible.html('<span>' + PRIX100ML.erreur + '</span>');
    }
  }

  $form.on('found_variation', function (evt, variation) { maj(variation); });
  $form.on('reset_data', function () { $('#prix-100ml').html(''); }); // Removed the default message
});

