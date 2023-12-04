function toggleMenu() {
  var sidebar = document.getElementById("sidebar");
  sidebar.classList.toggle("active");
}

function untoggleMenu() {
  var sidebar = document.getElementById("sidebar");
  sidebar.classList.remove("active");
}

window.addEventListener("resize", function () {
  if (window.innerWidth > 1090) {
    var sidebar = document.getElementById("sidebar");
    sidebar.classList.remove("active");
  }
});
