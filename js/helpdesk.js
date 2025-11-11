document.addEventListener('DOMContentLoaded', function() {
    var arg = document.getElementsByClassName("section");
    var i;

    for (i = 0; i < arg.length; i++) {
      arg[i].addEventListener("click", function() {
        this.classList.toggle("active");
        var panel = this.nextElementSibling;
        if (panel.style.maxHeight) {
          panel.style.maxHeight = null;
        } else {
          panel.style.maxHeight = panel.scrollHeight + "px";
        } 
      });
    }
});