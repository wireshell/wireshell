$(document).ready(function() {

  function manipulateHtml() {
    var $headlines = $('.rst-content').find('blockquote');

    $headlines.each(function() {
      var $this = $(this);
      var answer = $this.nextUntil('hr');
      var $wrapper = $('<div class="answer">');

      $wrapper.append(answer);
      $this.addClass('question');
      $this.after($wrapper);
    });

    toggleSlideState($headlines);
  }

  function toggleSlideState($headlines) {
    var $answers = $('.answer');

    $answers.slideUp();
    $headlines.removeClass('question--active');

    $headlines.on('click', function() {
      var $this = $(this);

      $this.next().slideToggle();
      $this.toggleClass('question--active');
    });

  }

  // === FAQ
  manipulateHtml();

});
