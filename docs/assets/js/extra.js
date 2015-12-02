$(document).ready(function() {

  // === FAQ
  function manipulateHtml() {
    var contentWrapper = document.getElementsByClassName('rst-content')[0];
    var headlines = contentWrapper.getElementsByTagName('blockquote');

    for (index = 0; index < headlines.length; ++index) {
      var answer = $(headlines[index]).nextUntil('hr');
      headlines[index].className = 'question';
      headlines[index].insertAdjacentHTML('afterend', '<div class="answer"></div>');

      var wrapper = headlines[index].nextElementSibling;

      for (key = 0; key < answer.length; ++key) {
        wrapper.appendChild(answer[key]);
      }
    }

    toggleSlideState($(headlines));
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

  manipulateHtml();

});
